<?php

namespace App\Http\Controllers;

use OdooClient\Client;

class OdooPOS extends Controller
{
    protected $client;
    protected $currency;

    public function __construct()
    {
        $url = config('app.odoo_url');
        $database = config('app.odoo_db', '');
        $user = config('app.odoo_username');
        $password = config('app.odoo_password');
        $this->client = new Client($url, $database, $user, $password);
        $this->currency = config('app.odoowoo_currency');
    }

    public function getDailySalesReport($recipients, $date)
    {
        $fields = array(
            'id',
            'name',
            'start_at',
            'cash_register_difference',
            'config_id',
            'total_payments_amount',
            'order_count'
        );

        $criteria = array(
            array('start_at', '>', date($date." 00:00:00")),
            array('stop_at', '<', date($date." 23:59:59")),
            array('state', '=', 'closed')
        );

        try {
            $sessions = $this->client->search_read('pos.session', $criteria, $fields);
        } catch (\Throwable $th) {
            throw $th;
        }

        $_sessions = [];
        if (!empty($sessions)) {
            $j = 0;
            foreach ($sessions as $session) {
                if ($session['total_payments_amount'] > 0) {
                    $fields = array(
                        'id',
                        'payment_method_id',
                        'amount'
                    );

                    $criteria = array(
                        array('session_id', '=', $session['id'])
                    );

                    try {
                        $payments = $this->client->search_read('pos.payment', $criteria, $fields);
                    } catch (\Throwable $th) {
                        throw $th;
                    }

                    $_sessions[$j]['name'] = $this->formatText($session['config_id'][1]);
                    $_sessions[$j]['ref'] = $this->formatText($session['name']);
                    $_sessions[$j]['payment_methods'] = [];

                    foreach ($payments as $payment) {
                        $index = $payment['payment_method_id'][1];
                        $value = $payment['amount'];
                        if (!isset($_sessions[$j]['payment_methods'][$index])) {
                            $_sessions[$j]['payment_methods'][$index] = $value;
                        } else {
                            $_sessions[$j]['payment_methods'][$index] += $value;
                        }
                    }
                    $_sessions[$j]['count'] = $session['order_count'];
                    $_sessions[$j]['total'] = $session['total_payments_amount'];
                    $j++;
                }
            }
        }

        $messages = [];
        if (!empty($_sessions)) {
            foreach ($_sessions as $_session) {
                $messages[] = $this->messageTemplate($_session);
            }
        }

        if (!empty($messages)) {
            foreach ($messages as $message) {
                $smsController = new SMSController();
                $smsController->sendMessage($recipients, $message);
            }
        }
    }

    private function formatText($input)
    {
        // Remove text within brackets and the brackets themselves
        $output = preg_replace('/\([^)]*\)/', '', $input);

        // Remove double spaces
        $output = preg_replace('/\s+/', ' ', $output);

        return trim($output);
    }

    private function messageTemplate($data) {
        $message = $data['name']. " ";
        $message .= "(".$data['ref'] . ") on ";
        $message .= date("d M Y") . " ";
        $message .= "Report:\n";
        $message .= "Orders: " . $data['count'] . "\n";
        foreach ($data['payment_methods'] as $key => $value) {
            $message .= $key . ": " . $this->currency . " " . number_format($value, 2) . "\n";
        }
        $message .= "Total: " . $this->currency . " " . number_format($data['total'], 2);
        return $message;
    }
}

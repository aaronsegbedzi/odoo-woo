<?php

namespace App\Console\Commands;

use App\Http\Controllers\WooCategory;
use App\Http\Controllers\WooProduct;
use Illuminate\Console\Command;
use Codexshaper\WooCommerce\Facades\Category;
use Codexshaper\WooCommerce\Facades\Product;
use OdooClient\Client;
use Codexshaper\WooCommerce\Facades\Variation;
use Codexshaper\WooCommerce\Facades\Attribute;
use App\Http\Controllers\WooAttribute;
use Codexshaper\WooCommerce\Facades\Term;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\OdooProduct;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woo:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('OdooWoo Test Command - ' . date("F j, Y, g:i a"));
        $csvFilePath = storage_path('app/public/shipping_methods.csv');
        if (!file_exists($csvFilePath)) {
            return "CSV file not found.";
        }
        $file = fopen($csvFilePath, 'r');
        fgetcsv($file);
        while (($row = fgetcsv($file)) !== false) {
            if ($row[2] == 1) {
                $id = $this->createShippingMethod();
                if ($id) {
                    sleep(1);
                    $response = $this->updateShippingMethod($id, $row[0], $row[1]);
                    if ($response) {
                        sleep(1);
                        $this->info("Created: " . $row[0]);
                    }
                }
            }
            // break;
        }
        fclose($file);
    }

    private function createShippingMethod()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://everythingbeautygh.com/wp-json/wc/v3/shipping/zones/1/methods',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(array('method_id' => 'flat_rate')),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic Y2tfMTgyMGY3NmQ0MzAxNjVkZGQxZWIzNDVmMGQwZjgwY2RmNTlmODI4Mzpjc183N2EyMWMyZjg3OTliOWMwMmNmZmRmYmM2ZmQzNGEzZDI4OWRkYmZl'
            ),
        ));
        $response = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($responseCode == 200) {
            $response = json_decode($response, true);
            return $response['id'];
        }
        return false;
    }

    private function updateShippingMethod($id, $name, $cost)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://everythingbeautygh.com/wp-json/wc/v3/shipping/zones/1/methods/' . $id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode(array('settings' => array('title' => $name, 'tax_status' => 'none', 'cost' => $cost))),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic Y2tfMTgyMGY3NmQ0MzAxNjVkZGQxZWIzNDVmMGQwZjgwY2RmNTlmODI4Mzpjc183N2EyMWMyZjg3OTliOWMwMmNmZmRmYmM2ZmQzNGEzZDI4OWRkYmZl'
            ),
        ));
        $response = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($responseCode == 200) {
            return true;
        }
        return false;
    }
}

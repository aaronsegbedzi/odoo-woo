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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $WooAttribute = new WooAttribute();
        //     $WooAttributes = $WooAttribute->getAttributes();
        //     dd($WooAttributes);

        // $url = env('ODOO_URL', '');
        // $database = env('ODOO_DB', '');
        // $user = env('ODOO_USERNAME', '');
        // $password = env('ODOO_PASSWORD', '');
        // $client = new Client($url, $database, $user, $password);

        // $fields = array(
        //     'id',
        //     'default_code',
        //     'name',
        //     'list_price',
        //     'x_brand',
        //     'qty_available',
        //     'categ_id',
        //     'has_configurable_attributes',
        //     'product_variant_ids',
        //     'x_ingredients',
        //     'x_directions',
        //     'description_sale'
        // );

        // $criteria = array(
        //     array('priority', '=', 0),
        //     array('image_1920', '!=', false),
        //     array('available_in_pos', '=', true),
        //     array('has_configurable_attributes', '=', true)
        // );

        // $products = $client->search_read('product.template', $criteria, $fields, 3);

        // dd($products);

        // $this->info('Processing ' . count($products) . ' products from Odoo.');

        // $payload = [];

        // if (!empty($products)) {
        //     foreach ($products as $product) {
        //         $payload[] = array(
        //             'id' => $product['id'],
        //             'name' => $product['name'],
        //             'sku' => $product['default_code'],
        //             'brand' => $product['x_brand'] == true ? $product['x_brand'] : 'None',
        //             'qty' => $product['qty_available'],
        //             'cat' => $product['categ_id'],
        //             'image' => env('ODOO_IMG_URL', '') . '/' . $product['id'] . '.jpg',
        //             'description' => $product['description_sale'] == true ? $product['description_sale'] : '',
        //             'directions' => $product['x_directions'] == true ? $product['x_directions'] : '',
        //             'ingredients' => $product['x_ingredients'] == true ? $product['x_ingredients'] : '',
        //             'variants' => $this->getProductVariants($product['id'])
        //         );
        //     }
        // }

        // foreach ($payload as $value) {

        //     $this->info('Synchronizing ' . $value['name'] . ' to WooCommerce.');

        //     $data = [
        //         'name' => $value['name'],
        //         'type' => 'variable',
        //         'description' => '',
        //         'short_description' => '',
        //         'categories' => [
        //             [
        //                 'id' => 15
        //             ],
        //         ],
        //         'images' => [
        //             [
        //                 'src' => $value['image']
        //             ]
        //         ],
        //         'attributes' => [
        //             [
        //                 'id' => 3,
        //                 'name' => 'Shades',
        //                 'position' => 0,
        //                 'visible' => false,
        //                 'variation' => true,
        //                 'options' => array_column($value['variants'], 'att_value')
        //             ]
        //         ],
        //         'meta_data' => [
        //             [
        //                 'key' => 'odoo_woo_id',
        //                 'value' => (string) $value['id']
        //             ]
        //         ]
        //     ];

        //     $product = Product::create($data);

        //     $this->info('Synchronized ' . $value['name'] . ' to WooCommerce.');

        //     sleep(5);

        //     $create = [];

        //     foreach ($value['variants'] as $value2) {
        //         $create[] = [
        //             'regular_price' => (string) $value2['price'],
        //             'stock_status' => $value2['qty'] > 0 ? 'instock' : 'outofstock',
        //             'stock_quantity' => $value2['qty'] > 0 ? $value2['qty'] : 0,
        //             'manage_stock' => true,
        //             'sku' => $value2['sku'],
        //             'image' => [
        //                 'src' => $value2['image']
        //             ],
        //             'attributes' => [
        //                 [
        //                     'id' => 3,
        //                     'option' => $value2['att_value']
        //                 ]
        //             ]
        //         ];
        //     }

        //     $batch = Variation::batch($product['id'], ['create' => $create]);

        //     $this->info('Synchronizing Variations!');

        //     sleep(5);
        // }

        $recipientEmail = env('MAIL_NOTIFICATIONS','');
        $subject = 'Test Email Address';
        $content = 'This is a test email sent via SMTP in Laravel.';

        Mail::raw($content, function ($message) use ($recipientEmail, $subject) {
            $message->to($recipientEmail)
                ->subject($subject);
        });

        $this->info("Test email sent!");
    }

    private function getProductVariants($id)
    {
        $url = env('ODOO_URL', '');
        $database = env('ODOO_DB', '');
        $user = env('ODOO_USERNAME', '');
        $password = env('ODOO_PASSWORD', '');
        $client = new Client($url, $database, $user, $password);

        $payload = [];
        $fields = array('id', 'product_template_variant_value_ids', 'qty_available', 'list_price', 'pricelist_item_count', 'default_code');
        $criteria = array(array('product_tmpl_id', '=', $id));
        $products = $client->search_read('product.product', $criteria, $fields);
        foreach ($products as $product) {
            $variant = $this->getVariantAttribute($product['product_template_variant_value_ids']);
            $payload[] = array(
                'id' => $product['id'],
                'sku' => $product['default_code'],
                'image' => env('ODOO_IMG_VARIANT_URL', '') . '/' . $product['id'] . '.jpg',
                'qty' => $product['qty_available'],
                'price' => $product['pricelist_item_count'] > 0 ? $price = $this->getVariantCustomPrice($product['id']) : $price = $product['list_price'],
                'att_name' => $variant['name'],
                'att_value' => $variant['value']
            );
        }
        return $payload;
    }

    private function getVariantAttribute($id)
    {
        $url = env('ODOO_URL', '');
        $database = env('ODOO_DB', '');
        $user = env('ODOO_USERNAME', '');
        $password = env('ODOO_PASSWORD', '');
        $client = new Client($url, $database, $user, $password);

        $payload = [];
        $fields = array('id', 'name', 'attribute_line_id');
        $criteria = array(array('id', '=', $id));
        $products = $client->search_read('product.template.attribute.value', $criteria, $fields);
        $payload = array(
            'name' => $products[0]['attribute_line_id'][1],
            'value' => $products[0]['name'],
        );
        return $payload;
    }

    // private function compareArrays($array1, $array2, $index1, $index2)
    // {
    //     $array1Indexed = array();
    //     $array2Indexed = array();

    //     foreach ($array1 as $item) {
    //         $array1Indexed[$item[$index1]] = $item;
    //     }

    //     foreach ($array2 as $item) {
    //         $array2Indexed[$item[$index2]] = $item;
    //     }

    //     $diff = array_diff_key($array1Indexed, $array2Indexed); // Differences between arrays
    //     $matches = array_intersect_key($array1Indexed, $array2Indexed); // Matches based on specific index

    //     return array('differences' => $diff, 'matches' => $matches);
    // }
}

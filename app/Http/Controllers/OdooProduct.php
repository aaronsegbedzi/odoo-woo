<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OdooClient\Client;
use Illuminate\Support\Facades\Log;

class OdooProduct extends Controller
{
    protected $client;

    public function __construct()
    {

        $url = env('ODOO_URL', '');

        $database = env('ODOO_DB', '');

        $user = env('ODOO_USERNAME', '');

        $password = env('ODOO_PASSWORD', '');

        $this->client = new Client($url, $database, $user, $password);
    }

    public function getProducts($limit = 1000)
    {

        $fields = array(
            'id',
            'default_code',
            'name',
            'list_price',
            'x_brand',
            'qty_available',
            'categ_id',
            'has_configurable_attributes',
            'product_variant_ids',
            'x_ingredients',
            'x_directions',
            'description_sale'
        );

        $criteria = array(
            // array('id', '=', 1837),
            array('image_1920', '!=', false),
            array('default_code', '!=', ''),
            array('available_in_pos', '=', true),
            array('has_configurable_attributes', '=', false)
        );

        $products = $this->client->search_read('product.template', $criteria, $fields, $limit);

        if (!empty($products)) {
            foreach ($products as $product) {
                $payload[] = array(
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'sku' => $product['default_code'],
                    'price' => $product['list_price'],
                    'brand' => $product['x_brand'] == true ? $product['x_brand'] : 'None',
                    'qty' => $product['qty_available'],
                    'cat' => $product['categ_id'],
                    'image' => env('ODOO_IMG_URL', '') . '/' . $product['id'] . '.jpg',
                    'description' => $product['description_sale'] == true ? $product['description_sale'] : '',
                    'directions' => $product['x_directions'] == true ? $product['x_directions'] : '',
                    'ingredients' => $product['x_ingredients'] == true ? $product['x_ingredients'] : '',
                    'is_variable' => $product['has_configurable_attributes']
                );
            }
            if (count($payload) > 0) {
                Log::info('Fetched : ' . count($payload) . ' products from Odoo.');
                // dd($payload);
                return $payload;
            }
        }

        exit();
    }

    public function getVariableProducts($limit = 1000)
    {

        $fields = array(
            'id',
            'name',
            'list_price',
            'x_brand',
            'categ_id',
            'has_configurable_attributes',
            'product_variant_ids',
            'x_ingredients',
            'x_directions',
            'description_sale'
        );

        $criteria = array(
            array('priority', '=', 1),
            array('image_1920', '!=', false),
            array('available_in_pos', '=', true),
            array('has_configurable_attributes', '=', true)
        );

        $products = $this->client->search_read('product.template', $criteria, $fields, $limit);

        if (!empty($products)) {
            foreach ($products as $product) {
                $payload[] = array(
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'brand' => $product['x_brand'] == true ? $product['x_brand'] : 'None',
                    'cat' => $product['categ_id'],
                    'image' => env('ODOO_IMG_URL', '') . '/' . $product['id'] . '.jpg',
                    'description' => $product['description_sale'] == true ? $product['description_sale'] : '',
                    'directions' => $product['x_directions'] == true ? $product['x_directions'] : '',
                    'ingredients' => $product['x_ingredients'] == true ? $product['x_ingredients'] : '',
                    'variants' => $this->getProductVariants($product['id'])
                );
            }
            if (count($payload) > 0) {
                Log::info('Fetched : ' . count($payload) . ' variable products from Odoo.');
                // dd($payload);
                return $payload;
            }
        }

        exit();
    }
  
    private function getProductVariants($id)
    {
        $payload = [];
        $fields = array('id', 'product_template_variant_value_ids', 'qty_available', 'list_price', 'pricelist_item_count', 'default_code');
        $criteria = array(array('product_tmpl_id', '=', $id));
        $products = $this->client->search_read('product.product', $criteria, $fields);
        foreach ($products as $product) {
            $variant = $this->getVariantAttribute($product['product_template_variant_value_ids']);
            $payload[] = array(
                'id' => $product['id'],
                'sku' => $product['default_code'],
                'image' => env('ODOO_IMG_VARIANT_URL', '') . '/' . $product['id'] . '.jpg',
                'qty' => $product['qty_available'],
                'price' => $product['pricelist_item_count'] > 0 ? $this->getVariantCustomPrice($product['id']) : $product['list_price'],
                'att_name' => $variant['name'],
                'att_value' => $variant['value']
            );
        }
        return $payload;
    }

    private function getVariantAttribute($id)
    {
        $payload = [];
        $fields = array('id', 'name', 'attribute_line_id');
        $criteria = array(array('id', '=', $id));
        $products = $this->client->search_read('product.template.attribute.value', $criteria, $fields);
        $payload = array(
            'name' => $products[0]['attribute_line_id'][1],
            'value' => $products[0]['name'],
        );
        return $payload;
    }

    private function getVariantCustomPrice($id) {
        $fields = array('id', 'fixed_price');
        $criteria = array(array('product_id', '=', $id));
        $products = $this->client->search_read('product.pricelist.item', $criteria, $fields);
        return $products[0]['fixed_price'];
    }

}

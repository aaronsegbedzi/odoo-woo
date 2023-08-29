<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\OdooProduct;
use App\Http\Controllers\WooCategory;
use App\Http\Controllers\WooAttribute;
use App\Http\Controllers\WooProduct;
use Codexshaper\WooCommerce\Facades\Product;
use Illuminate\Support\Facades\Log;

class SyncWooProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woo:sync {--images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Products in WooCommerce';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('OdooWoo Simple Products Synchronization Job - ' . date("F j, Y, g:i a"));
        $syncImages = $this->option('images');

        // Get the products from Odoo.
        $OdooProduct = new OdooProduct();
        $OdooProducts = $OdooProduct->getProducts();
        $this->info('Odoo Products Fetched: ' . count($OdooProducts));

        // Get the products from WooCommerce.
        $WooProduct = new WooProduct();
        $WooProducts = $WooProduct->getProducts();
        $this->info('Woo Products Fetched: ' . count($WooProducts));

        //CATEGORIES/////////////////////////////////////////////////////////////////////////////////////////
        // Get the categories from Odoo.
        $OdooCategories = [];
        foreach ($OdooProducts as $OdooProduct) {
            $OdooCategories[] = $OdooProduct['cat'];
        }
        $OdooCategories = array_values(array_map("unserialize", array_unique(array_map("serialize", $OdooCategories))));
        $this->info('Odoo Categories Fetched: ' . count($OdooCategories));

        // Get the categories from WooCommerce.
        $WooCategory = new WooCategory();
        $WooCategories = $WooCategory->getCategories();
        $this->info('Woo Categories Fetched: ' . count($WooCategories));

        // Create Categories if not exist in WooCommerce.
        $array1_ids = array_column($OdooCategories, 1);
        $array2_ids = array_column($WooCategories, 1);
        $CreateCategories = array_diff($array1_ids, $array2_ids);
        if (count($CreateCategories)) {
            $this->info('Creating ' . count($CreateCategories) . ' Categories in Woo.');
            foreach ($CreateCategories as $CreateCategory) {
                $WooCategory->createCategory($CreateCategory);
                $this->info('Created Category: ' . $CreateCategory);
            }
            // Get the categories from WooCommerce.
            $WooCategory = new WooCategory();
            $WooCategories = $WooCategory->getCategories();
        }

        // Merge Odoo and WooCommerce categories.
        $Categories = array_map(function ($item1) use ($WooCategories) {
            $matchingItems = array_filter($WooCategories, function ($item2) use ($item1) {
                return $item2[1] === $item1[1];
            });
            return array_merge($item1, ...$matchingItems);
        }, $OdooCategories);
        //CATEGORIES////////////////////////////////////////////////////////////////////////////////////////

        //BRANDS///////////////////////////////////////////////////////////////////////////////////////////
        // Get the Brand from Odoo.
        $OdooBrands = [];
        foreach ($OdooProducts as $OdooProduct) {
            if ($OdooProduct['brand']) {
                $OdooBrands[] = $OdooProduct['brand'];
            }
        }
        $OdooBrands = array_values(array_map("unserialize", array_unique(array_map("serialize", $OdooBrands))));
        $this->info('Odoo Brands Fetched: ' . count($OdooBrands));

        // Get the Brand from WooCommerce.
        $WooAttribute = new WooAttribute();
        $WooAttributeTerms = $WooAttribute->getAttributeTerms(env('WOOCOMMERCE_BRAND_ID', ''));
        $this->info('Woo Brands Fetched: ' . count($WooAttributeTerms));

        // Create Categories if not exist in WooCommerce.
        $array1_ids = $OdooBrands;
        $array2_ids = array_column($WooAttributeTerms, 1);
        $CreateTerms = array_diff($array1_ids, $array2_ids);
        if (count($CreateTerms) > 0) {
            $this->info('Creating ' . count($CreateTerms) . ' Brands in Woo.');
            foreach ($CreateTerms as $CreateTerm) {
                $WooAttribute->createAttributeTerm(env('WOOCOMMERCE_BRAND_ID', ''), $CreateTerm);
                $this->info('Created Brand: ' . $CreateTerm);
            }
            // Get the Brand from WooCommerce.
            $WooAttribute = new WooAttribute();
            $WooAttributeTerms = $WooAttribute->getAttributeTerms(env('WOOCOMMERCE_BRAND_ID', ''));
        }
        //BRANDS//////////////////////////////////////////////////////////////////////////////////////////

        $CreateProducts = [];
        $UpdateProducts = [];

        foreach ($OdooProducts as $OdooProduct) {
            $update = false;
            foreach ($WooProducts as $WooProduct) {
                if ($OdooProduct['sku'] == $WooProduct->sku) {
                    $OdooProduct['woo_id'] = $WooProduct->id;
                    $UpdateProducts[] = $OdooProduct;
                    $update = true;
                    break;
                }
            }
            if ($update == false) {
                $CreateProducts[] = $OdooProduct;
            }
        }

        $this->info('No. Product To Create: ' . count($CreateProducts) . ' | No. Product To Update: ' . count($UpdateProducts));

        $BatchCreate = [];
        $BatchUpdate = [];

        if (count($CreateProducts) > 0) {
            $this->info('Product Create Job Initiated');
            foreach ($CreateProducts as $CreateProduct) {
                $searchValue = $CreateProduct['cat'][0];
                $index = null;
                foreach ($Categories as $key => $element) {
                    if ($element[0] === $searchValue) {
                        $index = $key;
                        break;
                    }
                }

                $searchValue = $CreateProduct['brand'];
                $index2 = null;
                foreach ($WooAttributeTerms as $key => $element) {
                    if ($element[1] === $searchValue) {
                        $index2 = $key;
                        break;
                    }
                }

                $BatchCreate[] = [
                    'name' => $CreateProduct['name'],
                    'type' => 'simple',
                    'regular_price' => (string) $CreateProduct['price'],
                    'sku' => $CreateProduct['sku'],
                    'manage_stock' => true,
                    'stock_quantity' => $CreateProduct['qty'] > 0 ? $CreateProduct['qty'] : 0,
                    'stock_status' => $CreateProduct['qty'] > 0 ? 'instock' : 'outofstock',
                    'description' => $this->formatDescription($CreateProduct['description'], $CreateProduct['directions'],$CreateProduct['ingredients']),
                    'short_description' => $this->truncateString($CreateProduct['description']),
                    'categories' => [
                        [
                            'id' => $Categories[$index][2]
                        ]
                    ],
                    'images' => [
                        [
                            'src' => $CreateProduct['image']
                        ]
                    ],
                    'attributes' => [
                        [
                            'id' => env('WOOCOMMERCE_BRAND_ID', ''),
                            'name' => 'Brand',
                            'visible' => true,
                            'variation' => false,
                            'options' => [$WooAttributeTerms[$index2][1]]
                        ]
                    ],
                    'meta_data' => [
                        [
                            'key' => 'odoo_woo_id',
                            'value' => (string) $CreateProduct['id']
                        ]
                    ]
                ];
            }
            $batchSize = 50;
            $i = 1;
            $chunks = array_chunk($BatchCreate, $batchSize);
            foreach ($chunks as $chunk) {
                try {
                    $this->info('Batch ' . $i . ': ' . date("F j, Y, g:i a"));
                    $_batch = Product::batch(['create' => $chunk]);
                    $this->info('COMPLETED Batch ' . $i . ' @ ' . date("F j, Y, g:i a"));
                } catch (\Exception $e) {
                    $this->info('FAILED Batch ' . $i . ' - REASON: ' . $e->getMessage());
                }
                $i++;
                sleep(70);
            }
            $this->info('Product Create Job Completed');
        }

        if (count($UpdateProducts) > 0) {
            $this->info('Product Update Job Initiated');
            foreach ($UpdateProducts as $UpdateProduct) {
                $searchValue = $UpdateProduct['cat'][0];
                $index = null;
                foreach ($Categories as $key => $element) {
                    if ($element[0] === $searchValue) {
                        $index = $key;
                        break;
                    }
                }
                $searchValue = $UpdateProduct['brand'];
                $index2 = null;
                foreach ($WooAttributeTerms as $key => $element) {
                    if ($element[1] === $searchValue) {
                        $index2 = $key;
                        break;
                    }
                }
                if ($syncImages || date("Y-m-d") === date("Y-m-d", strtotime($UpdateProduct['updated']))) {
                    $BatchUpdate[] = [
                        'id' => $UpdateProduct['woo_id'],
                        'name' => $UpdateProduct['name'],
                        'type' => 'simple',
                        'regular_price' => (string) $UpdateProduct['price'],
                        'manage_stock' => true,
                        'stock_quantity' => $UpdateProduct['qty'] > 0 ? $UpdateProduct['qty'] : 0,
                        'stock_status' => $UpdateProduct['qty'] > 0 ? 'instock' : 'outofstock',
                        'description' => $this->formatDescription($UpdateProduct['description'], $UpdateProduct['directions'],$UpdateProduct['ingredients']),
                        'short_description' => $this->truncateString($UpdateProduct['description']),
                        'categories' => [
                            [
                                'id' => $Categories[$index][2]
                            ]
                        ],
                        'images' => [
                            [
                                'src' => $UpdateProduct['image']
                            ]
                        ],
                        'attributes' => [
                            [
                                'id' => env('WOOCOMMERCE_BRAND_ID', ''),
                                'name' => 'Brand',
                                'visible' => true,
                                'variation' => false,
                                'options' => [$WooAttributeTerms[$index2][1]]
                            ],
                        ],
                        'meta_data' => [
                            [
                                'key' => 'odoo_woo_id',
                                'value' => (string) $UpdateProduct['id']
                            ]
                        ]
                    ];
                } else {
                    $BatchUpdate[] = [
                        'id' => $UpdateProduct['woo_id'],
                        'name' => $UpdateProduct['name'],
                        'type' => 'simple',
                        'regular_price' => (string) $UpdateProduct['price'],
                        'manage_stock' => true,
                        'stock_quantity' => $UpdateProduct['qty'] > 0 ? $UpdateProduct['qty'] : 0,
                        'stock_status' => $UpdateProduct['qty'] > 0 ? 'instock' : 'outofstock',
                        'description' => $this->formatDescription($UpdateProduct['description'], $UpdateProduct['directions'],$UpdateProduct['ingredients']),
                        'short_description' => $this->truncateString($UpdateProduct['description']),
                        'categories' => [
                            [
                                'id' => $Categories[$index][2]
                            ]
                        ],
                        'attributes' => [
                            [
                                'id' => env('WOOCOMMERCE_BRAND_ID', ''),
                                'name' => 'Brand',
                                'visible' => true,
                                'variation' => false,
                                'options' => [$WooAttributeTerms[$index2][1]]
                            ],
                        ],
                        'meta_data' => [
                            [
                                'key' => 'odoo_woo_id',
                                'value' => (string) $UpdateProduct['id']
                            ]
                        ]
                    ];
                }
            }
            $batchSize = 50;
            $i = 1;
            $chunks = array_chunk($BatchUpdate, $batchSize);
            foreach ($chunks as $chunk) {
                try {
                    $this->info('Batch ' . $i . ': ' . date("F j, Y, g:i a"));
                    $_batch = Product::batch(['update' => $chunk]);
                    $this->info('COMPLETED Batch ' . $i . ' @ ' . date("F j, Y, g:i a"));
                } catch (\Exception $e) {
                    $this->info('FAILED Batch ' . $i . ' - REASON: ' . $e->getMessage());
                }
                $i++;
                sleep(70);
            }
            $this->info('Product Update Job Completed');
        }

        $this->info('OdooWoo Synchronization Completed. Have Fun :)');
    }

    private function formatDescription($description, $directions, $ingredients) {
        $text = '';
        if (!empty($description)){
            $text .= strip_tags(htmlspecialchars_decode($description));
        }
        if (!empty($directions)){
            $text .= "\n<h3>DIRECTIONS</h3>\n".strip_tags(htmlspecialchars_decode($directions));
        }
        if (!empty($ingredients)){
            $text .= "\n<h3>INGREDIENTS</h3>\n".strip_tags(htmlspecialchars_decode($ingredients));
        }
        return $text;
    }

    private function cutToEndOfLastSentence($text) {
        // Find the last occurrence of a period, question mark, or exclamation mark
        $lastSentenceEnd = max(strrpos($text, '.'), strrpos($text, '?'), strrpos($text, '!'));
    
        // If no valid sentence end is found, return the original text
        if ($lastSentenceEnd === false) {
            return $text;
        }
    
        // Cut the text to the end of the last sentence
        $cutText = substr($text, 0, $lastSentenceEnd + 1); // Include the sentence end punctuation
    
        return $cutText;
    }

    private function trimSentences($inputText) {
        // Split the input text into paragraphs
        $paragraphs = preg_split('/\n\s*\n/', $inputText);
    
        // Process each paragraph
        foreach ($paragraphs as &$paragraph) {
            // Split the paragraph into sentences
            $sentences = preg_split('/(?<=[.!?])\s+(?=[A-Z])/', $paragraph);
    
            // Trim each sentence
            foreach ($sentences as &$sentence) {
                $sentence = trim($sentence);
            }
    
            // Join the sentences back into the paragraph
            $paragraph = implode(' ', $sentences);
        }
    
        // Join the paragraphs back into the text
        $cleanedText = implode("\n\n", $paragraphs);
    
        return $cleanedText;
    }

    private function truncateString($inputText, $limit = 250) {

        $inputText = preg_replace('/[^\S\r\n]+/', ' ', $inputText);
        $inputText = preg_replace('/^(?=[^\s\r\n])\s+/m', '', $inputText);
        $paragraphs = preg_split('/\n\s*\n/', $inputText, 2, PREG_SPLIT_NO_EMPTY);
        
        // Check if there's at least one paragraph
        if (empty($paragraphs)) {
            return '';
        }
        
        // Get the first paragraph
        $firstParagraph = $this->trimSentences($paragraphs[0]);
        
        // Check the length of the first paragraph
        if (strlen($firstParagraph) <= $limit) {
            return $firstParagraph;
        } else {
            // Shorten the text to 250 characters
            return $this->cutToEndOfLastSentence(substr($firstParagraph, 0, $limit));
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\OdooProduct;
use App\Http\Controllers\WooCategory;
use App\Http\Controllers\WooAttribute;
use App\Http\Controllers\WooProduct;
use Codexshaper\WooCommerce\Facades\Product;
use Illuminate\Support\Facades\Log;
use Codexshaper\WooCommerce\Facades\Variation;

class SyncWooProductVariables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woo:sync-woo-product-variables {--images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Variable Products in WooCommerce';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->info('OdooWoo Variable Products Synchronization Job - ' . date("F j, Y, g:i a"));

        $syncImages = $this->option('images');

        // Get the products from Odoo.
        $OdooProduct = new OdooProduct();
        $OdooProducts = $OdooProduct->getVariableProducts();
        $this->info('Odoo Variable Products Fetched: ' . count($OdooProducts));

        // Get the products from WooCommerce.
        $WooProduct = new WooProduct();
        $WooProducts = $WooProduct->getVariableProducts();
        $this->info('Woo Variable Products Fetched: ' . count($WooProducts));

        //CATEGROIES//////////////////////////////////////////////////////////////////////////////////////////////////
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
            if (count($CreateCategories) > 0) {
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
        //CATEGROIES//////////////////////////////////////////////////////////////////////////////////////////////////

        //BRANDS//////////////////////////////////////////////////////////////////////////////////////////////////////
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

            // Create Brands if not exist in WooCommerce.
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
        //BRANDS//////////////////////////////////////////////////////////////////////////////////////////////////////

        //ATTRIBUTES//////////////////////////////////////////////////////////////////////////////////////////////////
            // Get the attributes from Odoo.
            $OdooAttributes = [];
            foreach ($OdooProducts as $OdooProduct) {
                foreach ($OdooProduct['variants'] as $value) {
                    $OdooAttributes[] = array($value['att_name']);
                }
            }
            $OdooAttributes = array_values(array_map("unserialize", array_unique(array_map("serialize", $OdooAttributes))));
            $this->info('Odoo Attributes Fetched: ' . count($OdooAttributes));

            // Get the attributes from WooCommerce.
            $WooAttribute = new WooAttribute();
            $WooAttributes = $WooAttribute->getAttributes();
            $this->info('Woo Attributes Fetched: ' . count($WooAttributes));

            // Create attributes if not exist in WooCommerce.
            $array1_ids = array_column($OdooAttributes, 0);
            $array2_ids = array_column($WooAttributes, 1);
            $CreateAttributes = array_diff($array1_ids, $array2_ids);

            if (count($CreateAttributes) > 0) {
                $this->info('Creating ' . count($CreateAttributes) . ' Attributes in Woo.');
                foreach ($CreateAttributes as $CreateAttribute) {
                    $WooAttribute->createAttribute($CreateAttribute);
                    $this->info('Created Attribute: ' . $CreateAttribute);
                }
                // Get the attributes from WooCommerce.
                $WooAttribute = new WooAttribute();
                $WooAttributes = $WooAttribute->getAttributes();
            }

            // Merge Odoo and WooCommerce attributes.
            $Attributes = $WooAttributes;
        //ATTRIBUTES//////////////////////////////////////////////////////////////////////////////////////////////////

        $CreateProducts = [];
        $UpdateProducts = [];

        foreach ($OdooProducts as $OdooProduct) {
            $update = false;
            foreach ($WooProducts as $WooProduct) {
                if ($OdooProduct['id'] == $this->getMetaValue($WooProduct->meta_data)) {
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

        $this->info('No. Product Variables To Create: ' . count($CreateProducts) . ' | No. Product Variables To Update: ' . count($UpdateProducts));

        if (count($CreateProducts) > 0) {
            $total = count($CreateProducts);
            $i = 1;
            $this->info('Product Create Job Initiated');
            foreach ($CreateProducts as $Product) {
                $searchValue = $Product['cat'][0];
                $index = null;
                foreach ($Categories as $key => $element) {
                    if ($element[0] === $searchValue) {
                        $index = $key;
                        break;
                    }
                }

                $searchValue = $Product['variants'][0]['att_name'];
                $index1 = null;
                foreach ($Attributes as $key => $element) {
                    if ($element[1] === $searchValue) {
                        $index1 = $key;
                        break;
                    }
                }

                $searchValue = $Product['brand'];
                $index2 = null;
                foreach ($WooAttributeTerms as $key => $element) {
                    if ($element[1] === $searchValue) {
                        $index2 = $key;
                        break;
                    }
                }

                $data = [
                    'name' => $Product['name'],
                    'status' => 'publish',
                    'type' => 'variable',
                    'description' => $this->formatDescription($Product['description'], $Product['directions'],$Product['ingredients']),
                    'short_description' => $this->truncateString($Product['description']),
                    'categories' => [
                        [
                            'id' => $Categories[$index][2]
                        ],
                    ],
                    'images' => [
                        [
                            'src' => $Product['image']
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
                        [
                            'id' => $Attributes[$index1][0],
                            'name' => $Attributes[$index1][1],
                            'position' => 0,
                            'visible' => false,
                            'variation' => true,
                            'options' => array_column($Product['variants'], 'att_value')
                        ]
                    ],
                    'meta_data' => [
                        [
                            'key' => 'odoo_woo_id',
                            'value' => (string) $Product['id']
                        ]
                    ]
                ];

                try {
                    $product = Product::create($data);
                } catch (\Exception $e) {
                    $this->info('Failed to CREATE: '.$Product['name'].' REASON: '.$e->getMessage());
                    break;
                }


                sleep(5);

                $variants = [];

                foreach ($Product['variants'] as $Variant) {
                    $variants[] = [
                        'status' => 'publish',
                        'regular_price' => (string) $Variant['price'],
                        'stock_status' => $Variant['qty'] > 0 ? 'instock' : 'outofstock',
                        'stock_quantity' => $Variant['qty'] > 0 ? $Variant['qty'] : 0,
                        'manage_stock' => true,
                        'sku' => $Variant['sku'],
                        'image' => [
                            'src' => $Variant['image']
                        ],
                        'attributes' => [
                            [
                                'id' => $Attributes[$index1][0],
                                'option' => $Variant['att_value']
                            ]
                        ],
                        'meta_data' => [
                            [
                                'key' => 'odoo_woo_id',
                                'value' => (string) $Variant['id']
                            ]
                        ]
                    ];
                }

                try {
                    $_batch = Variation::batch($product['id'], ['create' => $variants]);
                } catch (\Exception $e) {
                    $this->info('Failed to CREATE: '.$Product['name'].' REASON: '.$e->getMessage());
                    break;
                }

                sleep(5);

                $this->info('Created Product ' . $i . '/' . $total);

                $i++;
            }
            $this->info('Product Create Job Completed');
        }

        if (count($UpdateProducts) > 0) {
            $total = count($UpdateProducts);
            $i = 1;
            $this->info('Product Update Job Initiated');
            foreach ($UpdateProducts as $Product) {
                $searchValue = $Product['cat'][0];
                $index = null;
                foreach ($Categories as $key => $element) {
                    if ($element[0] === $searchValue) {
                        $index = $key;
                        break;
                    }
                }

                $searchValue = $Product['variants'][0]['att_name'];
                $index1 = null;
                foreach ($Attributes as $key => $element) {
                    if ($element[1] === $searchValue) {
                        $index1 = $key;
                        break;
                    }
                }

                $searchValue = $Product['brand'];
                $index2 = null;
                foreach ($WooAttributeTerms as $key => $element) {
                    if ($element[1] === $searchValue) {
                        $index2 = $key;
                        break;
                    }
                }

                if ($syncImages) {
                    $data = [
                        'name' => $Product['name'],
                        'status' => 'publish',
                        'type' => 'variable',
                        'description' => $this->formatDescription($Product['description'], $Product['directions'],$Product['ingredients']),
                        'short_description' => $this->truncateString($Product['description']),
                        'categories' => [
                            [
                                'id' => $Categories[$index][2]
                            ],
                        ],
                        'images' => [
                            [
                                'src' => $Product['image']
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
                            [
                                'id' => $Attributes[$index1][0],
                                'name' => $Attributes[$index1][1],
                                'position' => 0,
                                'visible' => false,
                                'variation' => true,
                                'options' => array_column($Product['variants'], 'att_value')
                            ]
                        ]
                    ];
                } else {
                    $data = [
                        'name' => $Product['name'],
                        'status' => 'publish',
                        'type' => 'variable',
                        'description' => $this->formatDescription($Product['description'], $Product['directions'],$Product['ingredients']),
                        'short_description' => $this->truncateString($Product['description']),
                        'categories' => [
                            [
                                'id' => $Categories[$index][2]
                            ],
                        ],
                        'attributes' => [
                            [
                                'id' => env('WOOCOMMERCE_BRAND_ID', ''),
                                'name' => 'Brand',
                                'visible' => true,
                                'variation' => false,
                                'options' => [$WooAttributeTerms[$index2][1]]
                            ],
                            [
                                'id' => $Attributes[$index1][0],
                                'name' => $Attributes[$index1][1],
                                'position' => 0,
                                'visible' => false,
                                'variation' => true,
                                'options' => array_column($Product['variants'], 'att_value')
                            ]
                        ]
                    ];
                }

                try {
                    $product = Product::update($Product['woo_id'], $data);
                } catch (\Exception $e) {
                    $this->info('Failed to UPDATE: '.$Product['name'].' REASON: '.$e->getMessage());
                    break;
                }

                sleep(5);

                $WooProduct = new WooProduct();
                $WooVariations = $WooProduct->getVariations($Product['woo_id']);

                // Compare data sets for create/update with SKU.
                $CreateVariants = [];
                $UpdateVariants = [];

                foreach ($Product['variants'] as $OdooVariant) {
                    $update = false;
                    foreach ($WooVariations as $WooVariant) {
                        if ($OdooVariant['id'] == $this->getMetaValue($WooVariant->meta_data)) {
                            $OdooVariant['woo_id'] = $WooVariant->id;
                            $UpdateVariants[] = $OdooVariant;
                            $update = true;
                        }
                    }
                    if ($update == false) {
                        $CreateVariants[] = $OdooVariant;
                    }
                }

                $BatchCreateVariants = [];
                $BatchUpdateVariants = [];

                if (count($CreateVariants) > 0) {
                    foreach ($CreateVariants as $Variant) {
                        if ($syncImages) {
                            $BatchCreateVariants[] = [
                                'status' => 'publish',
                                'regular_price' => (string) $Variant['price'],
                                'stock_status' => $Variant['qty'] > 0 ? 'instock' : 'outofstock',
                                'stock_quantity' => $Variant['qty'] > 0 ? $Variant['qty'] : 0,
                                'manage_stock' => true,
                                'sku' => $Variant['sku'],
                                'image' => [
                                    'src' => $Variant['image']
                                ],
                                'attributes' => [
                                    [
                                        'id' => $Attributes[$index1][0],
                                        'option' => $Variant['att_value']
                                    ]
                                ],
                                'meta_data' => [
                                    [
                                        'key' => 'odoo_woo_id',
                                        'value' => (string) $Variant['id']
                                    ]
                                ]
                            ];
                        } else {
                            $BatchCreateVariants[] = [
                                'status' => 'publish',
                                'regular_price' => (string) $Variant['price'],
                                'stock_status' => $Variant['qty'] > 0 ? 'instock' : 'outofstock',
                                'stock_quantity' => $Variant['qty'] > 0 ? $Variant['qty'] : 0,
                                'manage_stock' => true,
                                'sku' => $Variant['sku'],
                                'attributes' => [
                                    [
                                        'id' => $Attributes[$index1][0],
                                        'option' => $Variant['att_value']
                                    ]
                                ],
                                'meta_data' => [
                                    [
                                        'key' => 'odoo_woo_id',
                                        'value' => (string) $Variant['id']
                                    ]
                                ]
                            ];
                        }
                    }
                }

                if (count($UpdateVariants) > 0) {
                    foreach ($UpdateVariants as $Variant) {
                        if ($syncImages) {
                            $BatchUpdateVariants[] = [
                                'id' => $Variant['woo_id'],
                                'status' => 'publish',
                                'regular_price' => (string) $Variant['price'],
                                'stock_status' => $Variant['qty'] > 0 ? 'instock' : 'outofstock',
                                'stock_quantity' => $Variant['qty'] > 0 ? $Variant['qty'] : 0,
                                'manage_stock' => true,
                                'sku' => $Variant['sku'],
                                'image' => [
                                    'src' => $Variant['image']
                                ]
                            ];
                        } else {
                            $BatchUpdateVariants[] = [
                                'id' => $Variant['woo_id'],
                                'status' => 'publish',
                                'regular_price' => (string) $Variant['price'],
                                'stock_status' => $Variant['qty'] > 0 ? 'instock' : 'outofstock',
                                'stock_quantity' => $Variant['qty'] > 0 ? $Variant['qty'] : 0,
                                'manage_stock' => true,
                                'sku' => $Variant['sku']
                            ];
                        }
                    }
                }

                try {
                    $_batch = Variation::batch($Product['woo_id'], ['create' => $BatchCreateVariants, 'update' => $BatchUpdateVariants]);
                } catch (\Exception $e) {
                    $this->info('Failed to UPDATE: '.$Product['name'].' REASON: '.$e->getMessage());
                    break;
                }

                sleep(5);

                $this->info('Updated Product ' . $i . '/' . $total);

                $i++;
            }

            $this->info('Product Update Job Completed');
        }

        $this->info('OdooWoo Synchronization Completed. Have Fun :)');
    }

    private function getMetaValue($array)
    {
        $id = null;
        if (isset($array) && !empty($array)) {
            foreach ($array as $value) {
                if ($value->key == 'odoo_woo_id') {
                    $id = $value->value;
                }
            }
        }
        return $id;
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
}

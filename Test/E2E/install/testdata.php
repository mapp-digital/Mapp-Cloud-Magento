<?php

class Magento_REST {
    const URL = "http://local.domain.com/rest";
    private $DATA_VARIATION = "";
    private $token;
    private $attribute_set_id;
    private $attribute_group_id;
    private $color_attribute_id;
    private $category1;
    private $category2;
    private $red_id;
    private $green_id;
    private $blue_id;

    function __construct() {
        $this->get_token();
        $this->get_attribute_data();
        $this->create_categories();
        $this->create_colors();
        
        $customer = $this->post("/default/V1/customers", $this->get_customer());
        print_r("Customer created: ". $customer["email"] . " - Test1234!\n");

        $simple_product = $this->post("/default/V1/products", $this->get_simple_product());
        print_r("Simple product created: " . $simple_product["name"] . "\n");

        $configurable_product = $this->post("/default/V1/products", $this->get_configurable_product());
        $variant1 = $this->post("/default/V1/products", $this->get_product_variant("green", $this->green_id));
        $variant2 = $this->post("/default/V1/products", $this->get_product_variant("red", $this->red_id));
        $variant3 = $this->post("/default/V1/products", $this->get_product_variant("blue", $this->blue_id));
        $this->link_variations($configurable_product, [$variant1, $variant2, $variant3]);
        print_r("Configurable product created: " . $configurable_product["name"] . "\n");

        $bundleProduct1 = $this->post("/default/V1/products", $this->get_simple_product("Mapp Bundle Item 1", "bundleitem1", 12));
        $bundleProduct2 = $this->post("/default/V1/products", $this->get_simple_product("Mapp Bundle Item 2", "bundleitem2", 99));
        $bundle = $this->post("/default/V1/products", $this->get_bundle($bundleProduct1, $bundleProduct2));
        print_r("Product Bundle created: " . $bundle["name"] . "\n");

        $this->post("/default/V1/products", $this->get_simple_product("Other Mapp Item 1", "otheritem1", 30));
        $this->post("/default/V1/products", $this->get_simple_product("Other Mapp Item 2", "otheritem2", 31));
        $this->post("/default/V1/products", $this->get_simple_product("Other Mapp Item 3", "otheritem3", 32));
        $this->post("/default/V1/products", $this->get_simple_product("Other Mapp Item 4", "otheritem4", 33));
        $this->post("/default/V1/products", $this->get_simple_product("Other Mapp Item 5", "otheritem5", 34));
        $this->post("/default/V1/products", $this->get_simple_product("Other Mapp Item 6", "otheritem6", 35));
        $this->post("/default/V1/products", $this->get_simple_product("Other Mapp Item 7", "otheritem7", 36));
        $this->post("/default/V1/products", $this->get_simple_product("Other Mapp Item 8", "otheritem8", 37));
        $this->post("/default/V1/products", $this->get_simple_product("Other Mapp Item 9", "otheritem9", 38));
        $this->post("/default/V1/products", $this->get_simple_product("Other Mapp Item 10", "otheritem10", 39));
        $this->post("/default/V1/products", $this->get_simple_product("Other Mapp Item 11", "otheritem11", 40));
        print_r("11 other simple products created!\n");
    }

    private function get_bundle($child1, $child2)
    {
        return [
            "product" => [
            "sku" => "mappbundle{$this->DATA_VARIATION}",
            "name" => "Mapp Bundle{$this->DATA_VARIATION}",
            "attribute_set_id" => $this->attribute_set_id,
            "status" => 1,
            "visibility" => 4,
            "type_id" => "bundle",
            "extension_attributes" => [
                "category_links" => [
                    [
                    "position" => 0,
                    "category_id" => $this->category1["id"]
                    ],
                    [
                    "position" => 0,
                    "category_id" => $this->category2["id"]
                    ]
                ],
                "stock_item" => [
                    "qty" => "123456",
                    "is_in_stock" => true
                ],
                "bundle_product_options" => [
                    [
                      "option_id" => 1,
                      "title" => "Mapp Bundle{$this->DATA_VARIATION}",
                      "required" => true,
                      "type" => "select",
                      "position" => 1,
                      "sku" => "mappbundle{$this->DATA_VARIATION}",
                      "product_links" => [
                        [
                          "id" => "1",
                          "sku" => $child1["sku"],
                          "option_id" => 1,
                          "qty" => 1,
                          "position" => 1,
                          "is_default" => false,
                          "price" => 12,
                          "price_type" => null,
                          "can_change_quantity" => 0
                        ],
                        [
                          "id" => "2",
                          "sku" => $child2["sku"],
                          "option_id" => 1,
                          "qty" => 1,
                          "position" => 2,
                          "is_default" => false,
                          "price" => 99,
                          "price_type" => null,
                          "can_change_quantity" => 0
                        ]
                      ]
                    ]
                  ]
                    
                ],
                "custom_attributes" => [
                    [
                        "attribute_code" => "price_view",
                        "value" => "0"
                    ]
                ]
        ]      
    ];
    }

    private function get_config_option()
    {
        return [
            "option" => [
                "attribute_id" => $this->color_attribute_id,
                "label" => "Color",
                "position" => 0,
                "is_use_default" => true,
                "values" => [
                  [
                    "value_index" => time()
                  ]
                ]
            ]
            
        ];
    }

    private function link_variations($parent, $children)
    {
        $sku = $parent["sku"];
        $this->post("/default/V1/configurable-products/$sku/options", $this->get_config_option());
        for ($i=0; $i < count($children); $i++) { 
            $variant = $children[$i];
           $this->post("/default/V1/configurable-products/$sku/child", $this->get_child_sku($variant));
        }
    }

    private function get_child_sku($variant)
    {
        $sku = $variant["sku"];
        return [
            "childSku" => $sku
        ];
    }

    private function get_simple_product($name ="Mapp Simple Product", $sku="mappsimple", $price=70) {
        $name = "{$name}{$this->DATA_VARIATION}";
        $sku = "{$sku}{$this->DATA_VARIATION}";
        return [
                "product" => [
                "sku" => $sku,
                "name" => $name,
                "attribute_set_id" => $this->attribute_set_id,
                "price" => $price,
                "status" => 1,
                "visibility" => 4,
                "type_id" => "simple",
                "extension_attributes" => [
                    "category_links" => [
                            [
                            "position" => 0,
                            "category_id" => $this->category1["id"]
                            ],
                            [
                            "position" => 0,
                            "category_id" => $this->category2["id"]
                            ]
                        ],
                        "stock_item" => [
                            "qty" => "123456",
                            "is_in_stock" => true
                        ]
                ]
            ]      
        ];
    }

    private function get_configurable_product()
    {
        return [
            "product" => [
                "sku" => "mappconfigurable{$this->DATA_VARIATION}",
                "name" => "Mapp Configurable Product{$this->DATA_VARIATION}",
                "attribute_set_id" => $this->attribute_set_id,
                "status" => 1,
                "visibility" => 4,
                "type_id" => "configurable",
                "extension_attributes" => [
                    "category_links" => [
                        [
                        "position" => 0,
                        "category_id" => $this->category1["id"]
                        ],
                        [
                        "position" => 0,
                        "category_id" => $this->category2["id"]
                        ]
                    ],
                    "stock_item" => [
                        "qty" => "123456",
                        "is_in_stock" => true
                    ]
                ]
            ]      
        ];
    }

    private function get_product_variant($color, $color_id)
    {
        return [
            "product" => [
                "sku" => "mappconfigurable{$color}{$this->DATA_VARIATION}",
                "name" => "Mapp Configurable Product $color {$this->DATA_VARIATION}",
                "attribute_set_id" => $this->attribute_set_id,
                "status" => 1,
                "visibility" => 1,
                "price" => 23,
                "type_id" => "simple",
                "extension_attributes" => [
                    "category_links" => [
                        [
                        "position" => 0,
                        "category_id" => $this->category1["id"]
                        ],
                        [
                        "position" => 0,
                        "category_id" => $this->category2["id"]
                        ]
                    ],
                    "stock_item" => [
                        "qty" => "123456",
                        "is_in_stock" => true
                    ]
                ],
                "custom_attributes" => [
                    [
                    "attribute_code" => "color",
                    "value" =>  $color_id
                    ]
                ]
            ]      
        ];
    }

    private function get_attribute_data() 
    {
        $query = $this->get_query("attribute_group_name", "Product Details");
        $attribute_set_goups = $this->get("/default/V1/products/attribute-sets/groups/list$query");
        $this->attribute_set_id = $attribute_set_goups["items"][0]["attribute_set_id"];
        $this->attribute_group_id = $attribute_set_goups["items"][0]["attribute_group_id"];
        $this->post("/default/V1/products/attribute-sets/attributes", $this->get_attribute_payload());

        $attribute_sets = $this->get("/default/V1/products/attribute-sets/sets/list?searchCriteria[currentPage]=1");
        $this->attribute_set_id = $attribute_sets["items"][0]["attribute_set_id"];
        $this->attribute_sets = $this->get("/default/V1/products/attribute-sets/$this->attribute_set_id/attributes");
        for ($i=0; $i < count($this->attribute_sets); $i++) {
            $el = $this->attribute_sets[$i];
            if($el["attribute_code"] === "color") {
                $this->color_attribute_id = $el["attribute_id"];
                break;
            }
        }
    }

    private function get_attribute_payload() {
        return [
            "attributeSetId" => $this->attribute_set_id,
            "attributeGroupId" => $this->attribute_group_id,
            "attributeCode" => "color",
            "sortOrder" => 99
        ];
    }

    private function create_categories()
    {
        $main_category = [
            "category" => [
                  "name" => "MAPP main category{$this->DATA_VARIATION}",
                  "is_active" => true,
                  "include_in_menu" => true
            ]   
        ];
    
        $sub_category = [
            "category" => [
                  "parent_id" => 0,
                  "name" => "MAPP sub category{$this->DATA_VARIATION}",
                  "is_active" => true,
                  "include_in_menu" => true
            ]  
        ];
        $this->category1 = $this->post("/default/V1/categories", $main_category);
        $sub_category["category"]["parent_id"] = $this->category1["id"];
        $this->category2 = $this->post("/default/V1/categories", $sub_category);
    }

    private function create_colors()
    {
        $this->red_id = $this->post("/default/V1/products/attributes/color/options", $this->get_color("red{$this->DATA_VARIATION}"));
        $this->green_id = $this->post("/default/V1/products/attributes/color/options", $this->get_color("green{$this->DATA_VARIATION}"));
        $this->blue_id = $this->post("/default/V1/products/attributes/color/options", $this->get_color("blue{$this->DATA_VARIATION}"));
    }

    private function get_color($color)
    {
        return [
            "option" => [
                "label" => $color
            ]
        ];
    }

    private function get_customer()
    {
        return ["customer"=> [

            "default_billing"=> "1",
            "default_shipping"=> "1",
            "email"=> "test{$this->DATA_VARIATION}@mapp.com",
            "firstname"=> "Mapp",
            "lastname"=> "User",
            "addresses"=> [
                [
                    "country_id"=> "DE",
                    "street"=> [
                        "mystreet 15"
                    ],
                    "telephone"=> "+4917699999999",
                    "postcode"=> "10164",
                    "city"=> "Berlin",
                    "firstname"=> "Mapp",
                    "lastname"=> "User",
                    "default_shipping"=> true,
                    "default_billing"=> true
                ]
            ]
        ],
         "password"=> "Test1234!"
    ];
    }

    private function get_query($field, $value, $condition = "eq")
    {
        $s = "searchCriteria[filter_groups][0][filters][0]";
        $v = urlencode($value);
        return "?".$s."[field]=".$field."&".$s."[value]=".$v."&".$s."[condition_type]=".$condition;
    }

    private function get_token()
    {   
        $data = array("username" => "admin", "password" => "test1234");
        $endpoint = "/V1/integration/admin/token";
        $this->token = $this->request("POST", $endpoint, $data);
    }

    private function post($endpoint, $body)
    {
        return $this->request("POST", $endpoint, $body);
    }

    private function get($endpoint)
    {
        return $this->request("GET", $endpoint);
    }

    private function request($method, $endpoint, $body = null)
    {
        $authorization = "Authorization: Bearer $this->token";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::URL . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if($method === "POST") {
            $data_string = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization));
        $response = curl_exec($ch);
        return json_decode($response, true);
    }
}

new Magento_REST();
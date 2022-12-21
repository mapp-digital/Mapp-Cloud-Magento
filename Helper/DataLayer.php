<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2021 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Helper;

class DataLayer
{
    /**
     * @var string
     */
    const DATA_DELIMITER = ';';

    /**
     * @var array
     */
    const REMAPP_TABLE = [
        'customerId' => 'customerCDBEmailSha256',
        'gender' => 'customerGender',
        'productCost' => 'productPrice',
        'productId' => 'productEntityId',
        'productCategories' => 'productAvailableInCategory',
        'currency' => 'orderCurrency',
        'totalOrderValue' => 'orderTotalDue',
        'addProductCost' => 'addProductPrice',
        'addProductId' => 'addProductEntityId',
        'addProductCategories' => 'addProductAvailableInCategory'
    ];

    /**
     * @var array
     */
    const REMAPP_TABLE_PAGE = [
        'contentCategory' => 'pageCategory1',
        'contentSubcategory' => 'pageCategory2',
        'internalSearch' => 'pageSearchTerm',
        'numberOfSearchResults' => 'pageSearchResults'
    ];

    /**
     * @param $data array
     * @return array
     */
    public static function mappify($data)
    {
        $data = self::makeRemapping($data, self::REMAPP_TABLE);
        if(isset($data['productQuantityAndStockStatusIsInStock']) && $data['productQuantityAndStockStatusIsInStock'] !== '1') {
            $data['productSoldOut'] = '1';
        }
        if(isset($data['productAvailableInCategory'][0])){
            $data['productCategory'] = $data['productAvailableInCategory'][0];
        }
        if(isset($data['productAvailableInCategory'][1])){
            $data['productSubCategory'] = $data['productAvailableInCategory'][1];
        }
        if(isset($data['orderDiscountAmount']) && $data['orderDiscountAmount'] !== '0.0000') {
            $data['couponValue'] = substr($data['orderDiscountAmount'], 1);
        }
        if(isset($data['orderShoppingCartStatus'])) {
            $data['shoppingCartStatus'] = $data['orderShoppingCartStatus'];
        }
        return $data;
    }

    /**
     * @param $data array
     * @return array
     */
    public static function mappifyPage($data)
    {
        return self::makeRemapping($data, self::REMAPP_TABLE_PAGE);
    }

    /**
     * @param $data array
     * @param $mappingTable array
     * @return array
     */
    private static function makeRemapping($data, $mappingTable)
    {
        foreach ($mappingTable as $mappKey => $oldKey) {
            if(isset($data[$oldKey])) {
                $data[$mappKey] = $data[$oldKey];
            }
        }
        return $data;
    }


    /**
     * @param $existingProducts array
     * @param $productToBeAdded array
     * @return array
     */
    public static function merge($existingProducts, $productToBeAdded)
    {
        // since merge is only called when there is more than one product we need at least one delimiter
        $requiredAmountOfDelimiters = isset($existingProducts['entity_id']) ?
            count(explode(self::DATA_DELIMITER, $existingProducts['entity_id'])) + 1 : 1;

        $allDataLayerAttributes = array_keys($existingProducts); // get all  existing keys
        foreach ($productToBeAdded as $key => $_) { // add all keys of new product
            $allDataLayerAttributes[] = $key;
        }
        $allDataLayerAttributes = array_unique($allDataLayerAttributes); // get rid of duplicates
        foreach ($allDataLayerAttributes as $dataLayerAttribute) {
            // if it's not in the new product, it has to be in the existing data, so we just need to add 1 delimiter
            if(!array_key_exists($dataLayerAttribute, $productToBeAdded)) {
                if(is_array($existingProducts[$dataLayerAttribute])) {
                    foreach ($existingProducts[$dataLayerAttribute] as $key => $_) {
                        $existingProducts[$dataLayerAttribute][$key] .= self::DATA_DELIMITER;
                    }
                } else {
                    $existingProducts[$dataLayerAttribute] .= self::DATA_DELIMITER;
                }
            } else {
                // Attribute is set in new product, so the question is: is there already data in the existing one(s)
                // If there is not, then we need to write the delimiters first, and then add the value from the new product
                if(!array_key_exists($dataLayerAttribute, $existingProducts)) {
                    if(is_array($productToBeAdded[$dataLayerAttribute])) {
                        $existingProducts[$dataLayerAttribute] = []; // init
                        foreach ($productToBeAdded[$dataLayerAttribute] as $key => $value) {
                            $existingProducts[$dataLayerAttribute][$key] = ''; // init sub-key
                            for($i = 1; $i < $requiredAmountOfDelimiters; $i++) {
                                $existingProducts[$dataLayerAttribute][$key] .= self::DATA_DELIMITER; // add delimiter
                            }
                            $existingProducts[$dataLayerAttribute][$key] .= $value;
                        }
                    } else {
                        $existingProducts[$dataLayerAttribute] = ''; // init
                        for($i = 1; $i < $requiredAmountOfDelimiters; $i++) {
                            $existingProducts[$dataLayerAttribute] .= self::DATA_DELIMITER; // add delimiter
                        }
                        $existingProducts[$dataLayerAttribute] .= $productToBeAdded[$dataLayerAttribute];
                    }
                } else {
                    // Attribute is set in new and in existing products. Complicated for arrays...
                    if(is_array($productToBeAdded[$dataLayerAttribute])) {
                        // For arrays, the keys might differ, so we have to add on per-key basis
                        // but first check if the existing data is an array too
                        if(is_array($existingProducts[$dataLayerAttribute])) {
                            // grab all possible keys
                            $allSubKeys = [];
                            foreach ($productToBeAdded[$dataLayerAttribute] as $key => $_) {
                                array_push($allSubKeys, $key);
                            }
                            foreach ($existingProducts[$dataLayerAttribute] as $key => $_) {
                                array_push($allSubKeys, $key);
                            }
                            $allSubKeys = array_unique($allSubKeys);
                            // now iterate over all keys
                            foreach ($allSubKeys as $subKey) {
                                // subkey available in existing data: add delimiter, and if set the new value or empty string otherwise
                                if(array_key_exists($subKey, $existingProducts[$dataLayerAttribute])) {
                                    $existingProducts[$dataLayerAttribute][$subKey] .= self::DATA_DELIMITER .
                                        (array_key_exists($subKey, $productToBeAdded[$dataLayerAttribute]) ? $productToBeAdded[$dataLayerAttribute][$subKey] : '');
                                } else {
                                    // subkey is not available in existing product, which means it has to be set in new product
                                    // Therefore init it, write delimiters, then value
                                    $existingProducts[$dataLayerAttribute][$subKey] = ''; // init
                                    for($i = 1; $i < $requiredAmountOfDelimiters; $i++) {
                                        $existingProducts[$dataLayerAttribute][$subKey] .= self::DATA_DELIMITER;
                                    }
                                    $existingProducts[$dataLayerAttribute][$subKey] .= $productToBeAdded[$dataLayerAttribute][$subKey];
                                }
                            }
                        } else {
                            // here we have the case that the existing data is a string, while the new data comes as an array
                            // In that case we write the existing value in front of every array value
                            $existingNonArrayData = $existingProducts[$dataLayerAttribute];
                            $existingProducts[$dataLayerAttribute] = [];
                            foreach ($productToBeAdded[$dataLayerAttribute] as $key => $value) {
                                $existingProducts[$dataLayerAttribute][$key] = $existingNonArrayData . self::DATA_DELIMITER . $value;
                            }
                        }
                    } else {
                        // add delimiter and new value to existing data
                        if(is_array($existingProducts[$dataLayerAttribute])) {
                            // it might be possible that the target is an array, in that case we add it to every entry
                            foreach ($existingProducts[$dataLayerAttribute] as $key => $_) {
                                $existingProducts[$dataLayerAttribute][$key] .= self::DATA_DELIMITER . $productToBeAdded[$dataLayerAttribute];
                            }
                        } else {
                            // otherwise we just add it to the existing string
                            $existingProducts[$dataLayerAttribute] .= self::DATA_DELIMITER . $productToBeAdded[$dataLayerAttribute];
                        }
                    }
                }
            }
        }
        return $existingProducts;
    }

    public static function getUrlFragment($product)
    {
        $url = $product->getProductUrl();
        preg_match("/.+\/(.+?)(?:$|\?)/", $url, $match);
        return $match[1];
    }
}

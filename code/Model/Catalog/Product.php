<?php

/**
 * This class allows developers to modify.
 *
 *   1. Which products that will be visable/searchable through Clerk services.
 *   2. The product information exported to clerk analysis engine.
 *
 * IMPORTANT: Changes to this class will be overwritten when this extension is
 * updated. Remember to backup this file before updating the module!!
 */
class Clerk_Clerk_Model_Catalog_Product extends Clerk_Clerk_Model_Catalog_Productbase
{
    /* Image size for clerk sliders */
    public $imageHeight = 210;
    public $imageWidth = 210;

    /**
     * Returns an array that will represent the Products Object when exported
     * to the Clerk engine. Developers should feel free modifying this method
     * to export additional data or customize already exported data.
     */
    public function getClerkExportData()
    {
        $data = new Varien_Object();

        $data->setAge((int)$this->getAge());
        $data->setCreatedAt(strtotime($this->getCreatedAt()));
        $data->setCategories(array_map('intval', $this->getCategoryIds()));
        $data->setDescription($this->getDescription() ? $this->getDescription() : '');
        $data->setHasTierprice($this->hasTierPrice());
        $data->setTierPriceValues($this->getTierPricesClerk());
        $data->setTierPriceQuantities($this->getTierPriceQuantitiesClerk());
        $data->setId((int)$this->getId());
        $data->setImage($this->getClerkImageUrl());
        $data->setIsOnSale($this->isOnSale());
        $data->setManufacturer($this->getManufacturer());
        $data->setMetaDescription($this->getMetaDescription());
        $data->setMetaKeywords($this->getMetaKeyword());
        $data->setMetaTitle($this->getMetaTitle());
        $data->setName($this->getName());
        $data->setPrice($this->getClerkRetailPrice());
        $data->setPriceFinalExclTax($this->getClerkFinalPrice());
        $data->setPriceFinalInclTax($this->getClerkFinalPriceInclTax());
        $data->setPriceRetailExclTax($this->getClerkRetailPrice());
        $data->setPriceRetailInclTax($this->getClerkRetailPriceInclTax());
        $data->setShortDescription($this->getShortDescription());
        $data->setSku($this->getSku());
        $data->setRatingPct($this->getRating());
        $data->setRating($this->getRating()/20);
        $data->setReviewCount($this->getReviewCount());
        $data->setProductType($this->getTypeId());
        $data->setUrl($this->getProductUrl());
        $data->setVisibility($this->getVisibility());
        $data->setDiscountPercent($this->getDiscountPercent());
        $data->setIsSaleable($this->isSaleable());
        $data->setStock(round($this->getStockItem()->getQty()));

        $attribute_to_select = str_replace(' ','',Mage::getStoreConfig('clerk/general/additional_fields'));

        $first_pass = true;

        if (!empty($attribute_to_select)) {

            $attribute_to_select = explode(',', $attribute_to_select);

            foreach ($attribute_to_select as $key => $value) {
                $variant_ids = [];
                $variant_stocks = [];
                $variant_attribute_labels = [];
                $variant_attribute_options = [];
                $variant_skus = [];
                $variant_prices = [];
                $variant_list_prices = [];

                switch($this->getTypeId()){
                    case "configurable":
                        $attrCode = str_replace(' ','', $value);
                        $mainAttrText = $this->getAttributeText($attrCode);
                        $attr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product',$attrCode);
                        if (null!==$attr->getId()){
                            if(!isset( $data[$attrCode])){
                                if($mainAttrText){
                                    $data[$attrCode.'_label'] = $mainAttrText;
                                }
                            }
                        }

                        $tier_sub_pricing = array();
                        $confchildIds = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($this->getId());
                        foreach($confchildIds[0] as $cid){
                            $colectinformation = "";
                            $simple_product = Mage::getModel('catalog/product')->load($cid);
                            $entity_attrCode = "entity_". $attrCode; // needed for id and such

                            if($first_pass){

                                $variant_tier_prices = $simple_product->getTierPrice();

                                if(count($variant_tier_prices) > 0){
                                    $variant_price = (float)$simple_product->getPrice();
                                    $variant_min_qty = (integer)$simple_product->getStockItem()->getMinSaleQty();
                                    $_qtys = array($variant_min_qty);
                                    $_prcs = array($variant_price);
                                    foreach($variant_tier_prices as $tier_price){
                                        $_qtys[] = (integer)$tier_price['price_qty'];
                                        $_prcs[] = (float)$tier_price['price'];
                                    }
                                    $tier_sub_pricing[$cid] = array(
                                        'quantities' => $_qtys,
                                        'prices' => $_prcs
                                    );
                                }

                                if($this->sanitizeAttributes($simple_product->getSku())){
                                    array_push($variant_skus, $simple_product->getSku());
                                }
                                if($this->sanitizeAttributes((integer)$simple_product->getStockItem()->getQty())){
                                    array_push($variant_stocks, (integer)$simple_product->getStockItem()->getQty());
                                }
                                if($this->sanitizeAttributes($simple_product->getId())){
                                    array_push($variant_ids, $simple_product->getId());
                                }
                                $price = Mage::getModel('catalogrule/rule')->calcProductPriceRule($simple_product,$simple_product->getFinalPrice());
                                if($this->sanitizeAttributes($price)){
                                    array_push($variant_prices, (float)$price);
                                }

                                $list_price = Mage::getModel('catalogrule/rule')->calcProductPriceRule($simple_product,$simple_product->getRegularPrice());
                                if($this->sanitizeAttributes($list_price)){
                                    array_push($variant_list_prices, (float)$list_price);
                                }
                            }

                            if(!is_array($simple_product->getAttributeText($attrCode))){
                                if($this->sanitizeAttributes(strval($simple_product->getAttributeText($attrCode)))){
                                    array_push($variant_attribute_options, strval($simple_product->getAttributeText($attrCode)));
                                }
                            }

                            $variant_label_object = $simple_product->getResource()->getAttribute($attrCode);

                            if($variant_label_object->usesSource()){
                                $variant_label_holder = $variant_label_object->getSource()->getOptionText($simple_product->getData($attrCode));
                                if($variant_label_holder !== false && !empty($variant_label_holder)){
                                    array_push($variant_attribute_labels, $variant_label_holder);
                                }
                            }

                        }

                        if(!empty(array_values(array_unique($variant_attribute_options)))){
                            $data["variant_" . $attrCode . "s"] = $this->flattenArray(array_values(array_unique($variant_attribute_options)));
                        }
                        if(!empty(array_values(array_unique($variant_attribute_labels)))){
                            $data["variant_" . $attrCode . "s_labels"] = $this->flattenArray(array_values(array_unique($variant_attribute_labels)));
                        }

                        if($first_pass){
                            if(!empty($variant_skus)){
                                $data['variant_skus'] = $variant_skus;
                            }
                            if(!empty($variant_skus)){
                                $data['variant_ids'] = $variant_ids;
                            }
                            if(!empty($variant_skus)){
                                $data['variant_stocks'] = $variant_stocks;
                            }
                            if(!empty($variant_skus)){
                                $data['variant_prices'] = $variant_prices;
                            }
                            if(!empty($variant_skus)){
                                $data['variant_list_prices'] = $variant_list_prices;
                            }
                            if(!empty($tier_sub_pricing)){
                                $data['has_tierprice'] = true;
                                foreach($tier_sub_pricing as $k => $v){
                                    $data['vtp_price_'.strval($k)] = $v['prices'];
                                    $data['vtp_qty_'.strval($k)] = $v['quantities'];
                                }
                            }
                        }
                        break;

                    case "grouped":
                        $attrCode = str_replace(' ','', $value);
                        $mainAttrText = $this->getAttributeText($attrCode);
                        $attr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product',$attrCode);
                        if (null!==$attr->getId()){
                            if(!isset( $data[$attrCode])){
                                if($mainAttrText){
                                    $data[$attrCode.'_label'] = $mainAttrText;
                                }
                            }
                        }
                        $simple_collection = Mage::getModel('catalog/product_type_grouped')->getAssociatedProducts($this);
                        $groupchildatributtes=[];
                        foreach($simple_collection as $simple_product){
                            $colectinformation = "";
                            $entity_attrCode = "entity_". $attrCode; // needed for id and such

                            if ($attr->getId() != null || $attr->getId() != ''){
                                $colectinformation = strval($simple_product->getAttributeText($attrCode));
                            };

                            if (is_null($colectinformation) || $colectinformation == ""){
                                $product_data = $simple_product->getData();
                                if(isset($product_data[$attrCode])){
                                    $colectinformation = strval($product_data[$attrCode]);
                                }else{
                                    if(isset($product_data[$entity_attrCode])){ // needed for id and such
                                        $colectinformation = strval($product_data[$entity_attrCode]);
                                    }
                                }
                            }

                            if($colectinformation != ""){
                                array_push($groupchildatributtes,$colectinformation);
                            }
                        }
                        $groupchildatributtes = array_values(array_unique($groupchildatributtes));
                        $data["child_" . $attrCode . "s"] = $groupchildatributtes;
                        break;

                    case "simple":
                        $attrCode = str_replace(' ','', $value);
                        $mainAttrText = $this->getAttributeText($attrCode);
                        $attr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product',$attrCode);
                        if (null!==$attr->getId()){
                            if(!isset( $data[$attrCode])){
                                if($mainAttrText){
                                    $data[$attrCode.'_label'] = $mainAttrText;
                                }
                            }
                        }
                        break;
                }
            }
        $first_pass = false;
        }

        Mage::dispatchEvent('clerk_get_export_data', array('product' => $this, 'data' => $data));

        return $data->toArray();
    }

    /**
     * Returns a boolean indicating whether a product should be included in
     * data export to Clerk. Thus, developers can use this function to filter
     * products exportet to clerk.
     *
     * NOTE: Please set $this->excludeReason as shown in the example below if
     * a product should be excluded, this will help debugging future errors
     * where products are not showing up correctly on the website.
     */
    public function setExcludeReason()
    {
        if ($this->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
            $this->excludeReason = 'Product not visible';
        }

        if (!Mage::getStoreConfigFlag('clerk/general/include_out_of_stock_products')) {
            if (!$this->isSalable()) {
                $this->excludeReason = 'Product is not saleable';
            }
        }

        if ($this->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
            $this->excludeReason = 'Product status says "disabled"';
        }
    }

    public function sanitizeAttributes($value)
    {
        if($value === "" || $value === NULL){
            return false;
        } else {
            return true;
        }
    }

  /**
   * Flatten array
   *
   * @param array $array
   * @return arrray $array
   */
  public function flattenArray($array)
  {
    $return = [];
    array_walk_recursive($array, function ($a) use (&$return) {
      $return[] = $a;
    });
    return $return;
  }

}

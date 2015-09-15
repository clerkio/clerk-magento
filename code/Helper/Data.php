<?php 
class Clerk_Clerk_Helper_Data extends Mage_Core_Helper_Abstract
{
    // INCLUDE ONLY SALEABLE PRODUCTS
    public function includeOnlySaleableProducts()
    {
        return true;
    }
    
    // FILTERS ADDED TO THE FEED PRODUCT COLLECTION
    public function getProductCollectionFilters()
    {
        $filters = array(
            'visibility' => array('neq'=>Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE),
            'status' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
        );
        
        return $filters;
    }

    // FILTERS ADDED TO THE FEED SALES COLLECTION
    public function getSalesCollectionFilters()
    {
        $filters = array(
            'status' => array('neq' => 'canceled'),
            'created_at' => array('gt' => date('Y-m-d H:i:s',strtotime(date('Y-m-d').' -1 year'))),
        );
        
        return $filters;
    }   
    
    public function getApiKey($storeId = 0)
    {
        if(!$storeId){
            $storeId = Mage::app()->getStore()->getId();
        }
        return trim(Mage::getStoreConfig('clerk/settings/apikey',$storeId));
    }
    
    public function getPrivateApiKey($storeId = 0)
    {
        if(!$storeId){
            $storeId = Mage::app()->getStore()->getId();
        }
        return trim(Mage::getStoreConfig('clerk/settings/privateapikey',$storeId));
    }
    
    // EXPORTS PRODUCT DATA TO FEED
    public function getProductData($_product)
    {
        $data = array();
        $data['id'] = (int)$_product->getId();
    
        $data['name'] = (string)$_product->getName();
        $data['description'] = (string)$_product->getDescription();
        $data['short_description'] = (string)$_product->getShortDescription();
        
        $data['price'] = (float)$_product->getPrice();  
        
        $final_price = $_product->getFinalPrice();
        $time = Mage::app()->getLocale()->storeTimeStamp(Mage::app()->getStore()->getId());
        $website_id = Mage::app()->getStore()->getWebsiteId();
        $customer_group_id = 0;

        $price_after_rule = Mage::getResourceModel('catalogrule/rule')->getRulePrice($time,$website_id,$customer_group_id,$_product->getId());

        if( $price_after_rule < $final_price && $price_after_rule != '' ) {
            $final_price = $price_after_rule;       
        }
         
        if($final_price < $_product->getPrice()) {
            $data['is_on_sale'] = true;
            $data['special_price'] = (float)$final_price;    
        }
        
        $data['categories'] = array_map('intval', $_product->getCategoryIds());
        $data['url'] = (string)$_product->getProductUrl();
        $data['sku'] = (string)$_product->getSku();     
        
        $imageHeight = (Mage::getStoreConfig('clerk/feeds/image_height')) ? Mage::getStoreConfig('clerk/feeds/image_height') : null;
        $imageWidth = (Mage::getStoreConfig('clerk/feeds/image_width')) ? Mage::getStoreConfig('clerk/feeds/image_width') : null;
        if(!$imageHeight){
            $data['image'] = (string)Mage::helper('catalog/image')->init($_product, 'small_image')->resize($imageWidth);
        }
        else{
            $data['image'] = (string)Mage::helper('catalog/image')->init($_product, 'small_image')->resize($imageWidth,$imageHeight);
        }

        $data['manufacturer'] = (string)$_product->getAttributeText('manufacturer');

        $data['meta_keywords'] = (string)$_product->getMetaKeyword();
        $data['meta_description'] = (string)$_product->getMetaDescription();
        $data['meta_title'] = (string)$_product->getMetaTitle();
        
        $now = time();
        $your_date = strtotime($_product->getCreatedAt());
        $datediff = $now - $your_date;
        $data['age'] = (int)floor($datediff/(60*60*24));
        
        // ADD EKSTRA DATA BELOW THIS POINT
        
        return $data;
    }

    // EXPORTS PRODUCT DATA TO FEED
    public function getCategoryData($_category)
    {
        $subcats_array = array();
        $children = Mage::getModel('catalog/category')->getCollection()
                        ->addFieldToFilter("parent_id",array("eq"=>$_category->getId()));
                
        foreach ($children as $child)
        {
            $subcats_array[] = (int)$child->getId();
        }
    
        $data = array();
        $data['id'] = (int)$_category->getId();
        $data['name'] = (string)$_category->getName();
        $data['subcategories'] = array_map('intval',$subcats_array);
        
        // ADD EKSTRA DATA BELOW THIS POINT
        
        return $data;
    }

    // Extracts data from order used for sales tracking
    public function getSalesData($_order, $feed = false)
    {
        $items = array();
        foreach($_order->getAllVisibleItems() as $item) {
            if($feed) {
                $object = (int)$item->getProductId();
            } else {                

                // compute pr item price including taxes and discounts.
                $total_before_deiscount = $item->getRowTotalInclTax();
                $total_with_discount = (float)($total_before_deiscount -
                                               $item->getDiscountAmount());
                $actual_product_price = (float)($total_with_discount /
                                                (int)$item->getQtyOrdered());

                $object = new stdClass();
                $object->id = (int)$item->getProductId();
                $object->quantity = (int)$item->getQtyOrdered();
                $object->price = $actual_product_price;
            }
            array_push($items,$object);
        }
    
        $data = array();
        $data['id'] = (int)$_order->getIncrementId();
        $data['customer'] = (int)$_order->getCustomerId();
        $data['products'] = $items;
        $data['email'] = (string)$_order->getCustomerEmail();
        $data['time'] = (int)strtotime($_order->getCreatedAt());
    
        // ADD EKSTRA DATA BELOW THIS POINT
    
        return $data;
    }
    
    // TODO: remove tmp keyword argument and use build a json streamwriter instead.
    public function getFileName($store,$tmp = false)
    {
        $valid_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $key = Mage::getStoreConfig('clerk/settings/privateapikey', $store->getId());

        $prefix = '';
        foreach (str_split(hash('md5', $key, true)) as $byte){
            if (strlen($prefix) == 10){
                $prefix .= '_';
                break;
            }
            $prefix .= substr($valid_chars, (ord($byte) % 62), 1);
        }
        
        $filename = ($tmp) ? $prefix."clerk_".$store->getCode()."_tmp.json" : $prefix."clerk_".$store->getCode().".json";


        return $filename;
    }
}

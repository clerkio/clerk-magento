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
        return trim(Mage::getStoreConfig('clerk/settings/publicapikey',$storeId));
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

        /*
         * Bundle products with dynamic prices
         */
        if($_product->getTypeId() == 'bundle' && $_product->getPriceType() == '0')
        {

            if(Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX))
            {
                list($_minimalPrice, $_maximalPrice) = $_product->getPriceModel()->getTotalPrices($_product, null, true, false);
            } else {
                list($_minimalPrice, $_maximalPrice) = $_product->getPriceModel()->getTotalPrices($_product, null, null, false);
            }

            $data['price'] = $_minimalPrice;

            $currentDate    = Mage::getModel('core/date')->timestamp(time());
            $specialPrice   = $_product->getSpecialPrice();

            $specialPriceFrom   = $_product->getSpecialFrom();
            $specialpriceTo     = $_product->getSpecialTo();

            /*
             * Dynamic discount
             */
            if(!empty($specialPrice)
                && ((empty($specialPriceFrom) || strtotime($specialPriceFrom) >= $currentDate)
                    &&
                    (empty($specialpriceTo) || strtotime($specialpriceTo) <= $currentDate + 86400)
                )
            )
            {
                $oldPrice = round($_minimalPrice/$specialPrice * 100);
                $data['special_price']  = $_minimalPrice;
                $data['is_on_sale']     = true;
                $data['price']          = $oldPrice;
            }
        } else {
            $data['price'] = (float)$_product->getPrice();
            // Send is on sale as false, if there is no special price.
            $data['is_on_sale'] = false;

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
        }

        $data['categories'] = array_map('intval', $_product->getCategoryIds());
        $data['url'] = (string)$_product->getProductUrl();
        $data['sku'] = (string)$_product->getSku();

        // TODO 210 is hardcoded values, not the best, take from default conf
        $imageHeight = (Mage::getStoreConfig('clerk/datasync/custom_imagesize')) ? Mage::getStoreConfig('clerk/datasync/image_height') : 210;
        $imageWidth = (Mage::getStoreConfig('clerk/datasync/custom_imagesize')) ? Mage::getStoreConfig('clerk/datasync/image_width') : 210;
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

        //Get price of grouped products
			  if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED){
				      $_groupedProductChildPrices = array();
				      $_childProductIds = $_product->getTypeInstance()->getChildrenIds($_product->getId());
				      // 'Array' is apparently returned as an one element array, with the acutal 'array' inside.
				      $_childsList = array_values($_childProductIds)[0];
				      foreach ($_childsList as $_id) {
					           $_childProduct = Mage::getModel('catalog/product')->load($_id);
					           $_groupedProductChildPrices[] = $_childProduct['price'];
					           if ($_childProduct->getTierPrice() != null) {
						            foreach ($_childProduct as $_tier) {
							              $_groupedProductChildPrices[] = $_tier['price'];
						            }
					           }
				      }
				$_minValue = min($_groupedProductChildPrices);
				$data['price'] = (float)$_minValue;
        }

        return $data;
    }

    // EXPORTS PRODUCT DATA TO FEED
    public function getCategoryData($_category)
    {

        $subcats_array = array();
        $children = Mage::getModel('catalog/category')->getCollection()
            ->addFieldToFilter("parent_id", array("eq"=>$_category->getId()));

        foreach ($children as $child)
        {
            $subcats_array[] = (int)$child->getId();
        }

        $data = array();
        $data['id'] = (int)$_category->getId();
        $data['name'] = (string)$_category->getName();
        $data['subcategories'] = array_map('intval',$subcats_array);
        $data['url'] =  $_category->getUrl();

        $parent_id_index = array_search(
            $_category->parent_id, $data['subcategories']);

        Mage::log($data['subcategories']);
        if($parent_id_index !== false) {
            unset($data['subcategories'][$parent_id_index]);
            $data['subcategories'] = array_values($data['subcategories']);
        }

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

    public function getExtensionVersion()
    {
        return (string) Mage::getConfig()->getNode()->modules->Clerk_Clerk->version;
    }

}

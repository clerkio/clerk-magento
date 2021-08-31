<?php

class Clerk_Clerk_BasketController extends Mage_Core_Controller_Front_Action{
    
        public function basketAction()
        {
       
            if (Mage::helper('clerk')->getSetting('clerk/general/collect_baskets', Mage::app()->getStore()->getId()) == '1') {
                foreach (Mage::app()->getWebsites() as $website) {
                    foreach ($website->getGroups() as $group) {
                        $stores = $group->getStores();
                        foreach ($stores as $store) {
                            $cart_products = Mage::getModel('checkout/cart')->getQuote()->getAllVisibleItems();
                            $cart_product_ids = array();
                            foreach ($cart_products as $product) {
                                if (!in_array($product->getProduct()->getId(), $cart_product_ids)) {
                                    $cart_product_ids[] = $product->getProduct()->getId();
                                }
                            }
                        }
                    }
                }
            }

            echo implode(',',$cart_product_ids);

        } 

    }

<?php

class Clerk_Clerk_Helper_Data extends Mage_Core_Helper_Abstract
{
    /* Returns product attribute if present, otherwise return null */
    public function getAttributeSafe($product, $attribute)
    {
        $eavConfig = Mage::getModel('eav/config');
        /* @var $eavConfig Mage_Eav_Model_Config */

        $attributes = $eavConfig->getEntityAttributeCodes(
            Mage_Catalog_Model_Product::ENTITY, $product
        );

        if (in_array($attribute, $attributes)) {
            $value = trim($product->getAttributeText($attribute));

            return $value ? $value : null;
        }
    }

    /* Returns product with min price from grouped product */
    public function getMinPricedProductFromGroup($product)
    {
        $choosen = null;
        $associated = $product->getTypeInstance(true)
            ->getAssociatedProducts($product);
        foreach ($associated as $_product) {
            if ($choosen == null || $choosen->getFinalPrice() >
                                    $_product->getFinalPrice()) {
                $choosen = $_product;
            }
        }

        return $choosen;
    }

    public function floatEq($f1, $f2)
    {
        return abs($f1 - $f2) < 0.01;
    }

    public function isProductIdValid($productId)
    {
        $product = Mage::getModel('catalog/product')->load($productId);

        return !empty($product->getName());
    }

    /* Returns an array of store objects */
    public function getAllStores()
    {
        $data = array();
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $data[] = $store;
                }
            }
        }

        return $data;
    }

    /* Returns the setting if extension is active otherwise null */
    public function getSetting($path, $store = null)
    {
        if (!Mage::getStoreConfig('clerk/settings/active')) {
            return;
        }

        return Mage::getStoreConfig($path, $store);
    }
}

<?php

class Clerk_Clerk_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Returns product attribute if present
     *
     * @param $product
     * @param $attribute
     * @return null|string
     */
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

    /**
     * Returns product with min price from grouped product
     *
     * @param $product
     * @return null
     */
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

    /**
     * Determine if floats are equal
     *
     * @param $f1
     * @param $f2
     * @return bool
     */
    public function floatEq($f1, $f2)
    {
        return abs($f1 - $f2) < 0.01;
    }

    public function isProductIdValid($productId)
    {
        $product = Mage::getModel('catalog/product')->load($productId);

        return (bool) $product->getName();
    }

    /**
     * Get an array of store objects
     *
     * @return array
     */
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

    /**
     * Returns the config value if extension is active
     *
     * @param $path
     * @param null $store
     * @return string|void
     */
    public function getSetting($path, $store = null)
    {
        if (!Mage::getStoreConfig('clerk/general/active', $store)) {
            return;
        }

        return trim((string) Mage::getStoreConfig($path, $store));
    }
}

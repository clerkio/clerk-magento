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

        $data->setAge((int) $this->getAge());
        $data->setCategories(array_map('intval', $this->getCategoryIds()));
        $data->setDescription($this->getDescription());
        $data->setHasTierprice($this->hasTierPrice());
        $data->setId((int) $this->getId());
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
        $data->setUrl($this->getProductUrl());
        $data->setVisibility($this->getVisibility());
        $data->setDiscountPercent($this->getDiscountPercent());

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
        if ($this->getVisibility() == '1') {
            $this->excludeReason = 'Product not visible';
        }

        if (!$this->isSalable()) {
            $this->excludeReason = 'Product is not saleable';
        }

        if ($this->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
            $this->excludeReason = 'Product status says "disabled"';
        }

        if ($this->getTypeId() == 'downloadable' || $this->getTypeId() == 'virtual') {
            $this->excludeReason =
                "Clerk module does not support product type '".
                $this->getTypeId()."'";
        }
    }
}

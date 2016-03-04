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
        $data = array();
        $data['age'] = (int) $this->getAge();
        $data['categories'] = array_map('intval', $this->getCategoryIds());
        $data['description'] = $this->getDescription();
        $data['has_tierprice'] = $this->hasTierPrice();
        $data['id'] = (int) $this->getId();
        $data['image'] = $this->getClerkImageUrl();
        $data['is_on_sale'] = $this->isOnSale();
        $data['manufacturer'] = $this->getManufacturer();
        $data['meta_description'] = $this->getMetaDescription();
        $data['meta_keywords'] = $this->getMetaKeyword();
        $data['meta_title'] = $this->getMetaTitle();
        $data['name'] = $this->getName();
        $data['price'] = $this->getClerkRetailPrice();
        $data['price_final_excl_tax'] = $this->getClerkFinalPrice();
        $data['price_final_incl_tax'] = $this->getClerkFinalPriceInclTax();
        $data['price_retail_excl_tax'] = $this->getClerkRetailPrice();
        $data['price_retail_incl_tax'] = $this->getClerkRetailPriceInclTax();
        $data['short_description'] = $this->getShortDescription();
        $data['sku'] = $this->getSku();
        $data['url'] = $this->getProductUrl();
        $data['visibility'] = $this->getVisibility();

        return $data;
    }

    /**
     * Returns a boolean indicating weather a product should be included in
     * data export to Clerk. Thus, developers can use this function to filter
     * products exportet to clerk.
     *
     * NOTE: Please set $this->excludeReason as shown in the example below if
     * a product should be excluded, this will help debuggin future errors
     * where products are not showing up correctly on the website.
     */
    public function setExcludeReason()
    {
        if ($this->getVisibility() == '1') {
            $this->excludeReason = 'Product not visable';
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

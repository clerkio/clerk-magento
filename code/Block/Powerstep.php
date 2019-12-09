<?php

class Clerk_Clerk_Block_Powerstep extends Mage_Core_Block_Template
{
    /**
     * @var Mage_Catalog_Model_Product
     */
    protected $product;

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $quote;

    /**
     * Get last added product
     *
     * @return Mage_Catalog_Model_Product|Mage_Core_Model_Abstract
     */
    protected function getProduct()
    {
        if (is_null($this->product)) {
            $this->product = Mage::getModel('catalog/product')->load(
                Mage::getSingleton('checkout/session')->getLastAddedProductId());
        }

        return $this->product;
    }

    /**
     * Get quote
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function getQuote()
    {
        if (is_null($this->quote)) {
            $this->quote = Mage::getSingleton('checkout/session')->getQuote();
        }

        return $this->quote;
    }

    /**
     * Get Clerk templates
     *
     * @return array|mixed
     */
    protected function getTemplates()
    {
        if (is_null($this->templates)) {
            $this->templates = Mage::helper('clerk')->getSetting('clerk/powerstep/templates');
            $this->templates = array_map('trim', explode(',', $this->templates));
        }

        foreach ($this->templates as $key => $value) {

            $this->templates[$key] = str_replace(' ','', $value);

        }

        return $this->templates;
    }

    /**
     * Determine if we should show powerstep
     *
     * @return bool
     */
    public function shouldShow()
    {
        return Mage::getSingleton('core/session')->getFirePowerPopup(true);
    }

    /**
     * Get session
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _getSession()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * Get shopping cart items qty based on configuration (summary qty or items qty)
     *
     * @return int | float
     */
    public function getSummaryCount()
    {
        if (! $this->getData('summary_qty')) {
            $this->setData('summary_qty', Mage::getSingleton('checkout/cart')->getSummaryQty());
        }

        return $this->getData('summary_qty');
    }
}

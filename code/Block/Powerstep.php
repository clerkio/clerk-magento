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

    protected function getProduct()
    {
        if (is_null($this->product)) {
            $this->product = Mage::getModel('catalog/product')->load(
                Mage::getSingleton('checkout/session')->getLastAddedProductId());
        }

        return $this->product;
    }

    protected function getQuote()
    {
        if (is_null($this->quote)) {
            $this->quote = Mage::getSingleton('checkout/session')->getQuote();
        }

        return $this->quote;
    }

    protected function getTemplates()
    {
        if (is_null($this->templates)) {
            $this->templates = Mage::helper('clerk')->getSetting('clerk/powerstep/templates');
            $this->templates = array_map('trim', explode(',', $this->templates));
        }

        return $this->templates;
    }

    protected function getFirePopUp()
    {
        if (is_null($this->firePopUp)) {
            $this->firePopUp = Mage::getSingleton('core/session')->getFirePowerPopup(true);
        }

        return $this->firePopUp;
    }
}

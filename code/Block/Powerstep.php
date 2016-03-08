<?php

// app/code/local/Envato/Recentproducts/Block/Recentproducts.php
class Clerk_Clerk_Block_Powerstep extends Mage_Core_Block_Template
{
    protected function _construct()
    {
        $this->product = Mage::getModel('catalog/product')->load(
            Mage::getSingleton('checkout/session')->getLastAddedProductId());
        $this->quote = Mage::getSingleton('checkout/session')->getQuote();
        $this->templates = Mage::helper('clerk')->getSetting('clerk/powerstep/templates');
        $this->templates = array_map('trim', explode(',', $this->templates));
        $this->firePopUp = Mage::getSingleton('core/session')->getFirePowerPopup(true);
    }
}

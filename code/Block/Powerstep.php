<?php

// app/code/local/Envato/Recentproducts/Block/Recentproducts.php
class Clerk_Clerk_Block_Powerstep extends Mage_Core_Block_Template {

	protected function _construct()
	{
        $productId = Mage::getSingleton('checkout/session')->getLastAddedProductId(true);
        $this->product = Mage::getModel('catalog/product')->load($productId);
        $this->fire = Mage::getSingleton('core/session')->getFirePowerPopup(true);
        $this->items = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();
	}

    protected function _prepareLayout()
    {
        $this->sidebar = $this->getLayout()->createBlock('checkout/cart_sidebar');
        $this->totals = $this->sidebar->getTotalsCache();
        parent::_prepareLayout();
    }
}

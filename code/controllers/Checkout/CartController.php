<?php
require_once(Mage::getModuleDir('controllers','Mage_Checkout').DS.'CartController.php');

class Clerk_Clerk_Checkout_CartController extends Mage_Checkout_CartController
{
	public function clerkAction()
    {
        $this->lastProductId = Mage::getModel('catalog/product')->load($productId);
        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');
        $this->_initLayoutMessages('checkout/session');
        $this->renderLayout();
    }
}

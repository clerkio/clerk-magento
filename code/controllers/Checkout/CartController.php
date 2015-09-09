<?php
require_once(Mage::getModuleDir('controllers','Mage_Checkout').DS.'CartController.php');

class Clerk_Clerk_Checkout_CartController extends Mage_Checkout_CartController
{
	public function clerkAction()
    {
	    if(Mage::getModel('core/cookie')->get('clerk_power_popup')){
		    $this->loadLayout();
			$this->_initLayoutMessages('catalog/session');
			$this->_initLayoutMessages('checkout/session');
			$this->renderLayout();
	    }
    	else{
	    	$this->_redirect('checkout/cart');
    	}
    }
}

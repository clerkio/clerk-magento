<?php

class Clerk_Clerk_Model_Observer
{
    /**
     * The function is run by the observer when a new product is added to the cart.
     */
    public function itemAddedToCard($observer)
    {
        $request = $observer->getEvent()->getRequest();
        if (Mage::helper('clerk')->getSetting('clerk/powerstep/type') == 'page') {
            $request->setParam('return_url', Mage::getBaseUrl().'checkout/cart/clerk');
        } else {
            $referer = $request->getHeader('referer');
            $request->setParam('return_url', $referer);
            Mage::getSingleton('core/session')->setFirePowerPopup(true);
        }
    }

    public function deleteProduct($observer)
    {
        $productId = $observer->getEvent()->getProduct()->getId();
        Mage::getModel('clerk/communicator')->deleteProductId($productId);
    }

    public function saveProduct($observer)
    {
        $productId = $observer->getEvent()->getProduct()->getId();
        Mage::getModel('clerk/communicator')->saveProductId($productId);
    }
}

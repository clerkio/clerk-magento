<?php

class Clerk_Clerk_Model_Observer
{
    /**
     * The function is run by the observer when a new product is added to the cart.
     *
     * @param Varien_Event_Observer $observer
     */
    public function itemAddedToCart(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('clerk')->getSetting('clerk/powerstep/active')) {
            return;
        }
        $request = $observer->getEvent()->getRequest();
        if (Mage::helper('clerk')->getSetting('clerk/powerstep/type') == 'page') {
            $request->setParam('return_url', Mage::getBaseUrl().'checkout/cart/clerk');
        } else {
            $referer = $request->getHeader('referer');
            $request->setParam('return_url', $referer);
            Mage::getSingleton('core/session')->setFirePowerPopup(true);
        }
    }

    /**
     * Sync single product
     *
     * @param $observer
     */
    public function syncProduct(Varien_Event_Observer $observer)
    {
        $productId = $observer->getEvent()->getProduct()->getId();
        Mage::getModel('clerk/communicator')->syncProduct($productId, $observer->getEvent()->getName());
    }

    /**
     * Mass sync products
     *
     * @param Varien_Event_Observer $observer
     */
    public function syncProducts(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getEvent()->getProductIds();

        foreach ($productIds as $productId) {
            Mage::getModel('clerk/communicator')->syncProduct($productId, $observer->getEvent()->getName());
        }
    }
}

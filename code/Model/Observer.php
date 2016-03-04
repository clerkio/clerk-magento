<?php

class Clerk_Clerk_Model_Observer
{
    /**
     * The function is run by the observer when a new product is added to the cart.
     */
    public function itemAddedToCard($observer)
    {
        Mage::getSingleton('core/session')->setFirePowerPopup(true);
        $request = $observer->getEvent()->getRequest();
        //$referer = $request->getHeader('referer');
        //$request->setParam('return_url', $referer);
        $request->setParam('return_url', Mage::getBaseUrl().'checkout/cart/clerk');
    }
}

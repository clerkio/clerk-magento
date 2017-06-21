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

    /**
     * Sync products on catalogrule save
     *
     * @param Varien_Event_Observer $observer
     */
    public function syncOnCatalogRuleSave(Varien_Event_Observer $observer)
    {
        /** @var Mage_CatalogRule_Model_Rule $catalogRule */
        $catalogRule = $observer->getEvent()->getRule();
        if ($catalogRule->getIsActive()) {
            //Request a resync of everything
            Mage::getModel('clerk/communicator')->syncAll();
        }
    }

    /**
     * Sync everything when image cache is cleared
     *
     * @param Varien_Event_Observer $observer
     */
    public function syncOnCleanCatalogImagesCacheAfter(Varien_Event_Observer $observer)
    {
        Mage::getModel('clerk/communicator')->syncAll();
    }

    /**
     * Allow the SCP ext configurable product id (cpid) to override
     * the product id sent to clerk. By default, Magento will store
     * two rows in sale_flat_order_item table; one for the simple +
     * one for the associated configurable. SCP doesn't work like this
     * so determine the configurable id from the product options instead.
     *
     * @param Varien_Event_Observer $observer
     */
    public function formatScpOrderItem(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('core')->isModuleEnabled('OrganicInternet_SimpleConfigurableProducts')) {
            return;
        }

        /** @var array $output */
        $output = $observer->getEvent()->getOutput();

        /** @var Mage_Sales_Model_Order_Item $_item */
        $_item = $observer->getEvent()->getItem();

        /** @var array $buyRequest */
        $buyRequest = $_item->getProductOptionByCode('info_buyRequest');

        if ($buyRequest && isset($buyRequest['cpid'])) {
            $output['id'] = (int) $buyRequest['cpid'];
        }
    }
}

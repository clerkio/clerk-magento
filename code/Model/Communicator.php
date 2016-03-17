<?php

class Clerk_Clerk_Model_Communicator extends Mage_Core_Helper_Abstract
{
    protected $_addEndpoint = 'https://api.clerk.io/v2/product/add';
    protected $_removeEndpoint = 'https://api.clerk.io/v2/product/remove';

    /*
     * This call will connect to the clerk api call either either add a
     * product or delete it. Because the module at this time does not store data
     * in the magento database we have no way of knowing if the product after
     * a given event have changed state (include/exclude), thus we have to sync
     * products even though they are already synced.
     *
     * In the future we should find a better solution to this problem.
     */
    public function syncProduct($productId, $eventname)
    {
        $isDeleteEvent = $eventname == 'catalog_product_delete_before';
        $product = Mage::getModel('clerk/product')->load($productId);
        $appEmulation = Mage::getSingleton('core/app_emulation');
        foreach ($product->getStoreIds() as $storeId) {
            $store_enabled = Mage::helper('clerk')->getSetting('clerk/general/active', $storeId);
            if (!$store_enabled) {
                continue;
            }
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
            $product = Mage::getModel('clerk/product')->load($productId);
            if ($isDeleteEvent || $product->isExcluded()) {
                $data = array();
                $data['id'] = $productId;
                $data['key'] = Mage::helper('clerk')->getSetting('clerk/general/publicapikey');
                $data['private_key'] = Mage::helper('clerk')->getSetting('clerk/general/privateapikey');
                $this->sendData($data, $this->_removeEndpoint);
            } else {
                $data = $product->getClerkExportData();
                $data['key'] = Mage::helper('clerk')->getSetting('clerk/general/publicapikey');
                $data['private_key'] = Mage::helper('clerk')->getSetting('clerk/general/privateapikey');
                $this->sendData($data, $this->_addEndpoint);
            }
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        }
    }

    public function sendData($data, $endpoint)
    {
        try {
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_exec($ch);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }
}

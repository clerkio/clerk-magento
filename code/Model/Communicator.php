<?php

class Clerk_Clerk_Model_Communicator extends Mage_Core_Helper_Abstract
{
    protected $_addEndpoint = 'https://api.clerk.io/v2/product/add';
    protected $_removeEndpoint = 'https://api.clerk.io/v2/product/remove';

    public function saveProductId($productId)
    {
        $appEmulation = Mage::getSingleton('core/app_emulation');
        foreach (Mage::app()->getStores() as $store) {
            if (!Mage::helper('clerk')->getSetting('clerk/general/active', $store->getStoreId()) ||
                isset($product->excludeReason)) {
                continue;
            }
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getStoreId());
            $product = Mage::getModel('clerk/product')->load($productId);
            $product->setExcludeReason();
            $data = $product->getClerkExportData();
            $data['key'] = Mage::helper('clerk')->getSetting('clerk/general/publicapikey');
            $data['private_key'] = Mage::helper('clerk')->getSetting('clerk/general/privateapikey');
            $this->sendData($data, $this->_addEndpoint);
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        }
    }

    public function deleteProductId($productId)
    {
        foreach (Mage::app()->getStores() as $store) {
            if (!Mage::helper('clerk')->getSetting('clerk/general/active', $store->getStoreId())) {
                continue;
            }
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getStoreId());
            $data = array();
            $data['id'] = $productId;
            $data['key'] = Mage::helper('clerk')->getSetting('clerk/general/publicapikey');
            $data['private_key'] = Mage::helper('clerk')->getSetting('clerk/general/privateapikey');
            $this->sendData($data, $this->_removeEndpoint);
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
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_exec($ch);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }
}

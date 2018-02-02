<?php

class Clerk_Clerk_Model_Communicator extends Mage_Core_Helper_Abstract
{
    /**
     * @var string
     */
    protected $baseUrl = 'https://api.clerk.io/v2/';

    protected $_addEndpoint = 'https://api.clerk.io/v2/product/add';
    protected $_removeEndpoint = 'https://api.clerk.io/v2/product/remove';

    const XML_PATH_PUBLIC_KEY = 'clerk/general/publicapikey';
    const XML_PATH_PRIVATE_KEY = 'clerk/general/privateapikey';

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
                $data['key'] = $this->getPublicKey($storeId);
                $data['private_key'] = $this->getPrivateKey($storeId);
                $this->sendData($data, $this->_removeEndpoint);
            } else {
                $data = $product->getClerkExportData();
                $data['key'] = $this->getPublicKey($storeId);
                $data['private_key'] = $this->getPrivateKey($storeId);
                $this->sendData($data, $this->_addEndpoint);
            }
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        }
    }

    /**
     * Remove product from Clerk
     *
     * @param $productId
     * @throws Mage_Core_Exception
     */
    public function removeProduct($productId)
    {
        $product = Mage::getModel('clerk/product')->load($productId);

        foreach ($product->getStoreIds() as $storeId) {
            $enabled = Mage::getStoreConfigFlag('clerk/general/active', $storeId);

            if ($enabled) {
                $data = array();
                $data['products'] = $productId;
                $data['key'] = $this->getPublicKey($storeId);
                $data['private_key'] = $this->getPrivateKey($storeId);

                $this->get('product/remove', $data);
            }
        }
    }

    /**
     * Get facet attributes
     *
     * @param $store
     * @return Zend_Http_Response
     * @throws Mage_Core_Exception
     */
    public function getFacetAttributes($store)
    {
        $data = array();
        $data['key'] = $this->getPublicKey($store);
        $data['private_key'] = $this->getPrivateKey($store);

        if ($store) {
            return $this->get('product/facets', $data);
        }
    }

    /**
     * Validate public & private keys
     *
     * @param $publicKey
     * @param $privateKey
     * @return Zend_Http_Response
     * @throws Mage_Core_Exception
     */
    public function keysValid($publicKey, $privateKey)
    {
        $data = [
            'key' => $publicKey,
            'private_key' => $privateKey,
        ];

        return $this->get('client/account/info', $data);
    }

    /**
     * Make Clerk synchronize everything
     */
    public function syncAll()
    {
        $endpoint = 'client/account/importer/start';

        foreach (Mage::app()->getStores() as $store) {
            $data = array(
                'key' => $this->getPublicKey($store->getId()),
                'private_key' => $this->getPrivateKey($store->getId()),
            );

            $this->get($endpoint, $data);
        }
    }

    /**
     * Send POST request to clerk API
     *
     * @param $data
     * @param $endpoint
     */
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

    /**
     * Perform a GET request to the Clerk API
     *
     * @param $endpoint
     * @param array $data
     *
     * @return Zend_Http_Response
     * @throws Mage_Core_Exception
     */
    private function get($endpoint, $data = array())
    {
        $url = $this->baseUrl . $endpoint;

        $client = new Varien_Http_Client();
        try {
            $response = $client->setUri($url)
                ->setParameterGet($data)
                ->request('GET');
        } catch (Zend_Http_Client_Exception $e) {
            Mage::throwException($e->getMessage());
        }

        return $response;
    }

    /**
     * Get public key
     *
     * @return mixed
     */
    private function getPublicKey($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_PUBLIC_KEY, $storeId);
    }

    /**
     * Get private key
     *
     * @return mixed
     */
    private function getPrivateKey($storeId = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_PRIVATE_KEY, $storeId);
    }
}

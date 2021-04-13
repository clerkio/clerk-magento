<?php
require_once(Mage::getBaseDir('code') . '/community/Clerk/Clerk/controllers/ClerkLogger.php');
class Clerk_Clerk_Model_Communicator extends Mage_Core_Helper_Abstract
{
    const XML_PATH_PUBLIC_KEY = 'clerk/general/publicapikey';
    const XML_PATH_PRIVATE_KEY = 'clerk/general/privateapikey';
    /**
     * @var string
     */
    protected $baseUrl = 'https://api.clerk.io/v2/';
    /**
     * @var
     */
    private $logger;

    /**
     * @param $productIds
     * @throws Exception
     */
    public function syncProduct($productIds)
    {
        $this->logger = new ClerkLogger();

        try {

            if (!is_array($productIds)) {
                $productIds = array($productIds);
            }

            $appEmulation = Mage::getSingleton('core/app_emulation');

            foreach (Mage::app()->getStores() as $store) {
                if (!Mage::helper('clerk')->getSetting('clerk/general/active', $store->getId())) {
                    continue;
                }

                $productData = [];

                $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getId());

                foreach ($productIds as $productId) {
                    $product = Mage::getModel('clerk/product')->load($productId);

                    if ($product->isExcluded()) {
                        $this->removeProduct($productId);
                    } else {
                        $productData[] = $product->getClerkExportData();
                    }
                }

                $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

                $data['key'] = $this->getPublicKey($store->getId());
                $data['private_key'] = $this->getPrivateKey($store->getId());
                $data['products'] = $productData;

                $this->post('product/add', $data);
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Communicator "syncProduct"', ['error' => $e->getMessage()]);

        }
    }

    /**
     * @param $productId
     * @throws Exception
     */
    public function removeProduct($productId)
    {
        $this->logger = new ClerkLogger();

        try {

            $product = Mage::getModel('clerk/product')->load($productId);

            foreach ($product->getStoreIds() as $storeId) {
                $enabled = Mage::getStoreConfigFlag('clerk/general/active', $storeId);

                if ($enabled) {
                    $data = [];
                    $data['products'] = json_encode([$productId]);
                    $data['key'] = $this->getPublicKey($storeId);
                    $data['private_key'] = $this->getPrivateKey($storeId);

                    $this->get('product/remove', $data);
                }
            }
        } catch (Exception $e) {

            $this->logger->error('ERROR Communicator "removeProduct"', ['error' => $e->getMessage()]);

        }

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

    /**
     * @param $endpoint
     * @param array $data
     * @return Zend_Http_Response
     * @throws Exception
     */
    private function get($endpoint, $data = [])
    {
        $this->logger = new ClerkLogger();
        try {
            $url = $this->baseUrl . $endpoint;
            $client = new Zend_Http_Client();

            try {
                $response = $client->setUri($url)
                    ->setParameterGet($data)
                    ->request('GET');
            } catch (Zend_Http_Client_Exception $e) {
                Mage::throwException($e->getMessage());
            }

            return $response;
        } catch (Exception $e) {

            $this->logger->error('ERROR Communicator "get"', ['error' => $e->getMessage()]);

        }

    }

    /**
     * @param $endpoint
     * @param array $data
     * @throws Exception
     */
    public function post($endpoint, $data = [])
    {
        $this->logger = new ClerkLogger();

        $url = $this->baseUrl . $endpoint;

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            $response = curl_exec($ch);
        } catch (Exception $e) {
            $this->logger->error('ERROR Communicator "post"', ['error' => $e->getMessage()]);
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    /**
     * @param $store
     * @return Zend_Http_Response
     * @throws Exception
     */
    public function getFacetAttributes($store)
    {
        $this->logger = new ClerkLogger();

        try {
            $data = [];
            $data['key'] = $this->getPublicKey($store);
            $data['private_key'] = $this->getPrivateKey($store);

            if ($store) {
                return $this->get('product/facets', $data);
            }
        } catch (Exception $e) {

            $this->logger->error('ERROR Communicator "getFacetAttributes"', ['error' => $e->getMessage()]);

        }

    }

    /**
     * @param $publicKey
     * @param $privateKey
     * @return Zend_Http_Response
     * @throws Exception
     */
    public function keysValid($publicKey, $privateKey)
    {

        $this->logger = new ClerkLogger();

        try {
            $data = [
                'key' => $publicKey,
                'private_key' => $privateKey,
            ];

            return $this->get('client/account/info', $data);

        } catch (Exception $e) {

            $this->logger->error('ERROR Communicator "keysValid"', ['error' => $e->getMessage()]);

        }
    }

    /**
     * Make Clerk synchronize everything
     */
    public function syncAll()
    {
        $this->logger = new ClerkLogger();

        try {

            $endpoint = 'client/account/importer/start';

            foreach (Mage::app()->getStores() as $store) {
                $data = [
                    'key' => $this->getPublicKey($store->getId()),
                    'private_key' => $this->getPrivateKey($store->getId()),
                ];

                $this->get($endpoint, $data);
            }
        } catch (Exception $e) {

            $this->logger->error('ERROR Communicator "syncAll"', ['error' => $e->getMessage()]);

        }

    }

    /**
     * @param $storeId
     * @return Zend_Http_Response
     * @throws Exception
     */
    public function getContent($storeId)
    {
        $this->logger = new ClerkLogger();

        try {
            $endpoint = 'client/account/content/list';

            $data = [
                'key' => $this->getPublicKey($storeId),
                'private_key' => $this->getPrivateKey($storeId),
            ];

            return $this->get($endpoint, $data);

        } catch (Exception $e) {

            $this->logger->error('ERROR Communicator "getContent"', ['error' => $e->getMessage()]);

        }
    }
}

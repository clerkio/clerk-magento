<?php
require_once(Mage::getBaseDir('code') . '/community/Clerk/Clerk/controllers/ClerkLogger.php');

class Clerk_Clerk_ApiController extends Mage_Core_Controller_Front_Action
{
    private $logger;

    /**
     * Set content-type header and validate keys
     *
     * @return Mage_Core_Controller_Front_Action
     * @throws Zend_Controller_Request_Exception
     */
    public function preDispatch()
    {
        $this->logger = new ClerkLogger();
        try {

            $this->logger->log('Validation of Public and private key is started', []);
            $this->setStore();
            $this->getResponse()->setHeader('Content-type', 'application/json');

            $input = $this->getRequest()->getHeader('CLERK-PRIVATE-KEY');
            $secret = Mage::helper('clerk')->getSetting('clerk/general/privateapikey');

            if (!$secret || $input !== trim($secret)) {
                $response = [
                    'error' => [
                        'code' => 403,
                        'message' => 'Invalid public or private key supplied'
                    ]
                ];
                $this->logger->warn('Invalid public or private key supplied', ['response' => $response]);
                $this->getResponse()
                    ->setHeader('HTTP/1.1', '403', true)
                    ->setBody(json_encode($response))
                    ->sendResponse();
                exit;
            }
            $this->logger->log('Public and private key is validated', []);
            return parent::preDispatch();
        } catch (Exception $e) {

            $this->logger->error('ERROR Key validation "preDispatch"', ['error' => $e]);

        }
    }

    /**
     * Set current store
     */
    private function setStore()
    {
        $storeid = $this->getRequest()->getParam('store');

        if (isset($storeid) && is_numeric($storeid)) {
            try {
                Mage::app()->getStore((int)$storeid);
                Mage::app()->setCurrentStore((int)$storeid);

                return;
            } catch (Exception $e) {
                $response = [
                    'error' => [
                        'code' => 400,
                        'message' => 'Store not found',
                        'store_id' => $storeid
                    ]
                ];
            }
        } else {
            $response = [
                'error' => [
                    'code' => 400,
                    'message' => 'Query string param "store" is required'
                ]
            ];
        }

        $this->getResponse()->setBody(json_encode($response))->sendResponse();
        exit;
    }

    /**
     * Return Clerk module version
     */
    public function versionAction()
    {
        $this->logger = new ClerkLogger();

        try {
            $this->logger->log('Fetching Version Started', ['']);
            $response = [
                'platform' => 'Magento',
                'version' => (string)Mage::getConfig()->getNode()->modules->Clerk_Clerk->version,
            ];

            $this->logger->log('Fetched Version Done', ['response' => $response]);

            $this->getResponse()->setBody(json_encode($response));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Version "versionAction"', ['error' => $e]);

        }
    }

    /**
     * This endpoint will list stores
     *
     */
    public function storeAction()
    {
        $this->logger = new ClerkLogger();
        try {

            $this->logger->log('Fetching Stores Started', []);
            $data = [];

            foreach (Mage::helper('clerk')->getAllStores() as $store) {
                $data[] = [
                    'id' => $store->getId(),
                    'name' => $store->getName(),
                    'active' => (bool)Mage::getStoreConfig('clerk/general/active', $store),
                ];
            }

            $this->logger->log('Fetched Stores Done', ['response' => $data]);

            $this->getResponse()->setBody(json_encode($data));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Store "storeAction"', ['error' => $e]);

        }
    }

    /**
     * Product endpoint for collection and single products
     *
     */
    public function productAction()
    {
        $this->logger = new ClerkLogger();
        try {

            $this->logger->log('Products Synchronization Started', []);
            // Handler for product endpoint. E.g.
            // http://store.com/clerk/api/product/id/24
            $id = $this->getRequest()->getParam('id', false);

            if ($id) {
                $id = $this->getIntParam('id');
                if (Mage::helper('clerk')->isProductIdValid($id)) {
                    $response = Mage::getModel('clerk/product')->load($id)->getInfo();
                } else {
                    $response = [
                        'error' => [
                            'code' => 404,
                            'message' => 'Product not found',
                            'product_id' => $id
                        ]
                    ];
                }
            } else {
                $page = $this->getIntParam('page');
                $limit = $this->getIntParam('limit');
                $page = Mage::getModel('clerk/productpage')->load((int)$page, $limit);
                $response = $page->array;
                $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
            }

            $this->logger->log('Products Synchronization Done', ['response' => $response]);
            $this->getResponse()->setBody(json_encode($response));

        } catch (Exception $e) {

            $this->logger->error('ERROR Products Synchronization "productAction"', ['error' => $e]);

        }
    }

    /**
     * Get int parameter, show error message if supplied param is not a number
     *
     * @param $key
     * @param null $errmsg
     * @return int
     */
    private function getIntParam($key, $errmsg = null)
    {
        $this->logger = new ClerkLogger();
        try {

            $this->logger->log('Fetching Parameters Started', []);
            $value = $this->getRequest()->getParam($key);

            if (!is_numeric($value)) {
                $this->getResponse()->setHeader('HTTP/1.0', '400', true);

                if (isset($errmsg)) {
                    $response = [
                        'error' => [
                            'code' => 400,
                            'message' => $errmsg,
                            'value' => $value
                        ]
                    ];
                } else {
                    $response = [
                        'error' => [
                            'code' => 400,
                            'message' => "Query string '" . $key . "' is required and must be integer",
                            'value' => $value
                        ]
                    ];
                }
                $this->logger->warn('WARN Fetching Parameters', ['response' => $response]);
                $this->getResponse()->setBody(json_encode($response))->sendResponse();
                exit;
            }
            $this->logger->log('Fetched Parameters Done', ['response' => $value]);
            return (int)$value;

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Parameters "getIntParam"', ['error' => $e]);

        }
    }

    /**
     * Endpoint for category import
     *
     * @throws Mage_Core_Model_Store_Exception
     */
    public function categoryAction()
    {
        $this->logger = new ClerkLogger();
        try {

            $this->logger->log('Category Synchronization Started', []);
            $page = $this->getIntParam('page');
            $limit = $this->getIntParam('limit');

            $rootCategoryId = Mage::app()->getStore()->getRootCategoryId();

            $categories = Mage::getModel('catalog/category')
                ->getCollection()
                ->addIsActiveFilter()
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('path', ['like' => "1/{$rootCategoryId}/%"])
                ->setOrder('entity_id', Varien_Db_Select::SQL_ASC)
                ->setPageSize($limit)
                ->setCurPage($page);

            $items = [];

            foreach ($categories as $category) {
                //Get children categories
                $children = $category->getResource()->getChildren($category, false);

                $data = [
                    'id' => (int)$category->getId(),
                    'name' => $category->getName(),
                    'url' => $category->getUrl(),
                    'subcategories' => array_map('intval', $children),
                ];

                $items[] = $data;
            }

            $this->getResponse()->setHeader('Total-Page-Count', $categories->getLastPageNumber());

            if ($page > $categories->getLastPageNumber()) {
                $this->getResponse()->setBody(json_encode([]));
            } else {
                $this->logger->log('Category Synchronization Done', ['response' => $items]);
                $this->getResponse()->setBody(json_encode($items));
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Category Synchronization "categoryAction"', ['error' => $e]);

        }
    }

    /**
     * Endpoint for order import
     *
     */
    public function orderAction()
    {
        $this->logger = new ClerkLogger();
        try {

            $this->logger->log('Order Synchronization Started', []);
            $page = $this->getIntParam('page');
            $limit = $this->getIntParam('limit');
            $days = $this->getIntParam('days');

            if (Mage::getStoreConfigFlag('clerk/general/disable_order_synchronization')) {
                $this->logger->log('Order Synchronization is disabled', []);
                $this->getResponse()->setBody(json_encode([]));
            } else {
                $page = Mage::getModel('clerk/orderpage')->load($page, $limit, $days);
                $this->logger->log('Order Synchronization Done', []);
                $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
                $this->getResponse()->setBody(json_encode($page->array));
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Order Synchronization "orderAction"', ['error' => $e]);

        }
    }
}

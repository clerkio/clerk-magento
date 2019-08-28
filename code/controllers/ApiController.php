<?php
require_once(Mage::getBaseDir('code') . '/community/Clerk/Clerk/controllers/ClerkLogger.php');

class Clerk_Clerk_ApiController extends Mage_Core_Controller_Front_Action
{
    private $logger;

    /**
     * @return Mage_Core_Controller_Front_Action
     * @throws Exception
     */
    public function preDispatch()
    {
        $this->logger = new ClerkLogger();
        try {

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

            return parent::preDispatch();
        } catch (Exception $e) {

            $this->logger->error('ERROR Key validation "preDispatch"', ['error' => $e->getMessage()]);

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

            $response = [
                'platform' => 'Magento',
                'version' => (string)Mage::getConfig()->getNode()->modules->Clerk_Clerk->version,
            ];

            $this->getResponse()->setBody(json_encode($response));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Version "versionAction"', $e->getMessage());

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

            $data = [];

            foreach (Mage::helper('clerk')->getAllStores() as $store) {
                $data[] = [
                    'id' => $store->getId(),
                    'name' => $store->getName(),
                    'active' => (bool)Mage::getStoreConfig('clerk/general/active', $store),
                ];
            }

            $this->getResponse()->setBody(json_encode($data));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Store "storeAction"', $e->getMessage());

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

            $this->logger->log('Products Fetched', ['response' => $response]);
            $this->getResponse()->setBody(json_encode($response));

        } catch (Exception $e) {

            $this->logger->error('ERROR Products Synchronization "productAction"', $e->getMessage());

        }
    }

    /**
     * @param $key
     * @param null $errmsg
     * @return int
     * @throws Exception
     */
    private function getIntParam($key, $errmsg = null)
    {
        $this->logger = new ClerkLogger();
        try {

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

                $this->getResponse()->setBody(json_encode($response))->sendResponse();
                exit;
            }

            return (int)$value;

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Parameters "getIntParam"', $e->getMessage());

        }
    }

    /**
     * @throws Exception
     */
    public function categoryAction()
    {
        $this->logger = new ClerkLogger();
        try {

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
                $this->logger->log('Categories Fetched', ['response' => $items]);
                $this->getResponse()->setBody(json_encode($items));
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Category Synchronization "categoryAction"', $e->getMessage());

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

            $page = $this->getIntParam('page');
            $limit = $this->getIntParam('limit');
            $days = $this->getIntParam('days');

            if (Mage::getStoreConfigFlag('clerk/general/disable_order_synchronization')) {
                $this->logger->log('Order Synchronization is disabled', ['response' => '']);
                $this->getResponse()->setBody(json_encode([]));
            } else {
                $page = Mage::getModel('clerk/orderpage')->load($page, $limit, $days);
                $this->logger->log('Order Fetched', ['response' => '']);
                $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
                $this->getResponse()->setBody(json_encode($page->array));
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Order Synchronization "orderAction"', $e->getMessage());

        }
    }
}

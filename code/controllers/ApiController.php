<?php

class Clerk_Clerk_ApiController extends Mage_Core_Controller_Front_Action
{
    /**
     * Set content-type header and validate keys
     *
     * @return Mage_Core_Controller_Front_Action
     * @throws Zend_Controller_Request_Exception
     */
    public function preDispatch()
    {
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

            $this->getResponse()
                ->setHeader('HTTP/1.1', '403', true)
                ->setBody(json_encode($response))
                ->sendResponse();
            exit;
        }

        return parent::preDispatch();
    }

    /**
     * Return Clerk module version
     */
    public function versionAction()
    {
        $response = [
            'platform' => 'Magento',
            'version' => (string) Mage::getConfig()->getNode()->modules->Clerk_Clerk->version,
        ];

        $this->getResponse()->setBody(json_encode($response));
    }

    /**
     * This endpoint will list stores
     *
     * @throws Zend_Controller_Request_Exception
     */
    public function storeAction()
    {
        $this->setStore();
        $data = [];

        foreach (Mage::helper('clerk')->getAllStores() as $store) {
            $data[] = [
                'id' => $store->getId(),
                'name' => $store->getName(),
                'active' => (bool) Mage::getStoreConfig('clerk/general/active', $store),
            ];
        }

        $this->getResponse()->setBody(json_encode($data));
    }

    /**
     * Product endpoint for collection and single products
     *
     * @throws Zend_Controller_Request_Exception
     */
    public function productAction()
    {
        $this->setStore();

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

        $this->getResponse()->setBody(json_encode($response));
    }

    /**
     * Endpoint for category import
     *
     * @throws Mage_Core_Model_Store_Exception
     * @throws Zend_Controller_Request_Exception
     */
    public function categoryAction()
    {
        $this->setStore();

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
                'id' => (int) $category->getId(),
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
            $this->getResponse()->setBody(json_encode($items));
        }
    }

    /**
     * Endpoint for order import
     *
     * @throws Zend_Controller_Request_Exception
     */
    public function orderAction()
    {
        $this->setStore();

        $page = $this->getIntParam('page');
        $limit = $this->getIntParam('limit');
        $days = $this->getIntParam('days');

        if (Mage::getStoreConfigFlag('clerk/general/disable_order_synchronization')) {
            $this->getResponse()->setBody(json_encode([]));
        } else {
            $page = Mage::getModel('clerk/orderpage')->load($page, $limit, $days);
            $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
            $this->getResponse()->setBody(json_encode($page->array));
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
                        'message' => "Query string '".$key."' is required and must be integer",
                        'value' => $value
                    ]
                ];
            }
            $this->getResponse()->setBody(json_encode($response))->sendResponse();
            exit;
        }

        return (int) $value;
    }

    /**
     * Set current store
     */
    private function setStore()
    {
        $storeid = $this->getRequest()->getParam('store');

        if (isset($storeid) && is_numeric($storeid)) {
            try {
                Mage::app()->getStore((int) $storeid);
                Mage::app()->setCurrentStore((int) $storeid);

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
}

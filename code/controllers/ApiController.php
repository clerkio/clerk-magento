<?php

class Clerk_Clerk_ApiController extends Mage_Core_Controller_Front_Action
{
    /**
     * Set content-type header
     *
     * @return Mage_Core_Controller_Front_Action
     */
    public function preDispatch()
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');

        return parent::preDispatch();
    }

    /**
     * This endpoint will list stores
     *
     * @throws Zend_Controller_Request_Exception
     */
    public function storeAction()
    {
        $this->authenticate();
        $data = array();
        foreach (Mage::helper('clerk')->getAllStores() as $store) {
            $data[] = array(
                'id' => $store->getId(),
                'name' => $store->getName(),
                'active' => (bool) Mage::getStoreConfig('clerk/general/active', $store),
            );
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
        $this->authenticate();

        // Handler for product endpoint. E.g.
        // http://store.com/clerk/api/product/id/24
        $id = $this->getRequest()->getParam('id', false);

        if ($id) {
            $id = $this->getIntParam('id');
            if (Mage::helper('clerk')->isProductIdValid($id)) {
                $data = Mage::getModel('clerk/product')->load($id)->getInfo();
            } else {
                $data = array('Error' => 'Product not found');
            }
        } else {
            $page = $this->getIntParam('page');
            $limit = $this->getIntParam('limit');
            $page = Mage::getModel('clerk/productpage')->load((int)$page, $limit);
            $data = $page->array;
            $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
        }

        $this->getResponse()->setBody(json_encode($data));
    }

    /**
     * Endpoint for category import
     *
     * @throws Mage_Core_Model_Store_Exception
     * @throws Zend_Controller_Request_Exception
     */
    public function categoryAction()
    {
        $this->authenticate();

        $pageparam = $this->getIntParam('page');
        $limit = $this->getIntParam('limit');

        $collection = new Varien_Data_Collection();
        $paginator = new Clerk_Clerk_Model_Paginator($collection);

        $rootCategoryId = Mage::app()->getStore()->getRootCategoryId();

        $categories = Mage::getModel('catalog/category')
            ->getCollection()
            ->addIsActiveFilter()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('path', array('like' => "1/{$rootCategoryId}/%"))
            ->setOrder('entity_id', Varien_Db_Select::SQL_ASC)
            ->setPageSize($limit)
            ->setCurPage($pageparam);

        foreach ($categories as $category) {
            //Get children categories
            $children = $category->getResource()->getChildren($category);

            $data = array(
                'id' => (int) $category->getId(),
                'name' => $category->getName(),
                'url' => $category->getUrl(),
                'subcategories' => array_map('intval', $children),
            );

            $item = new Varien_Object();
            $item->setData($data);

            $collection->addItem($item);
        }

        if (Mage::getStoreConfigFlag('clerk/general/sync_cms_pages')) {
            $pages = Mage::getModel('cms/page')
                ->getCollection()
                ->addFieldToFilter('is_active', '1')
                ->addStoreFilter(Mage::app()->getStore()->getId());

            foreach ($pages as $page) {
                $data = array(
                    'id' => (int) $page->getId() + 10000,
                    'name' => $page->getTitle(),
                    'url' => Mage::helper('cms/page')->getPageUrl($page->getId()),
                    'subcategories' => [],
                );

                $item = new Varien_Object();
                $item->setData($data);

                $collection->addItem($item);
            }
        }

        $collection->setPageSize($limit);
        $collection->setCurPage($pageparam + 1);

        $this->getResponse()->setHeader('Total-Page-Count', $collection->getLastPageNumber() - 1);

        if ($pageparam > $collection->getLastPageNumber() - 1) {
            $this->getResponse()->setBody(json_encode([]));
        } else {
            $iterator = $paginator->getIterator();

            $items = [];
            foreach ($iterator as $item) {
                $items[] = $item->toArray();
            }

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
        $this->authenticate();

        $page = $this->getIntParam('page');
        $limit = $this->getIntParam('limit');
        $days = $this->getIntParam('days');

        if (Mage::getStoreConfigFlag('clerk/general/disable_order_synchronization')) {
            $this->getResponse()->setBody(json_encode(array()));
        } else {
            $page = Mage::getModel('clerk/orderpage')->load($page, $limit, $days);
            $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
            $this->getResponse()->setBody(json_encode($page->array));
        }
    }

    /**
     * Validate request
     *
     * @throws Zend_Controller_Request_Exception
     */
    private function authenticate()
    {
        $this->setStore();
        $this->getResponse()->setBody(json_encode(array('Error' => 'Not Authorized')));

        $input = $this->getRequest()->getHeader('CLERK-PRIVATE-KEY');
        $secret = Mage::helper('clerk')->getSetting('clerk/general/privateapikey');

        if (!$secret || $input != trim($secret)) {
            $this->getResponse()->setHeader('HTTP/1.0', '401', true);
            die($this->getResponse());
        }
    }

    /* Helper function extracting params, this function also does the
     * errorhandling is param is missing */
    private function getIntParam($key, $errmsg = null)
    {
        $value = $this->getRequest()->getParam($key);
        if (!is_numeric($value)) {
            $this->getResponse()->setHeader('HTTP/1.0', '404', true);
            if (isset($errmsg)) {
                $data = array('Error' => $errmsg);
            } else {
                $data = array('Error' => "Query string '".$key."' is required and must be integer");
            }
            $this->getResponse()->setBody(json_encode($data));
            die($this->getResponse());
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
                $data = array('Error' => 'Store not found');
            }
        } else {
            $data = array('Error' => "Query string param 'store' is required");
        }

        $this->getResponse()->setBody(json_encode($data));
        die($this->getResponse());
    }
}

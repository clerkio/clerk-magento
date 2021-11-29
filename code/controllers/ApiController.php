<?php
require_once(Mage::getBaseDir('code') . '/community/Clerk/Clerk/controllers/ClerkLogger.php');

class Clerk_Clerk_ApiController extends Mage_Core_Controller_Front_Action
{
    /**
     *
     */
    const XML_PATH_COLLECT_PAGES = 'clerk/general/collect_pages';

    /**
     * @var
     */
    private $logger;

    /**
     * @return Mage_Core_Controller_Front_Action
     * @throws Exception
     */
    public function preDispatch()
    {

        $i = Mage::getVersionInfo();
        $version = trim("{$i['major']}.{$i['minor']}.{$i['revision']}" . ($i['patch'] != '' ? ".{$i['patch']}" : "")
            . "-{$i['stability']}{$i['number']}", '.-');
        header('User-Agent: ClerkExtensionBot Magento 1/v' . $version . ' clerk/v' .(string)Mage::getConfig()->getNode()->modules->Clerk_Clerk->version . ' PHP/v' . phpversion());
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

            $i = Mage::getVersionInfo();
            $version = trim("{$i['major']}.{$i['minor']}.{$i['revision']}" . ($i['patch'] != '' ? ".{$i['patch']}" : "")
                . "-{$i['stability']}{$i['number']}", '.-');

            $response = [
                'platform' => 'Magento',
                'platform_version' => $version,
                'clerk_version' => (string)Mage::getConfig()->getNode()->modules->Clerk_Clerk->version,
                'php_version' => phpversion()
            ];

            $this->getResponse()->setBody(json_encode($response));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Version "versionAction"', $e->getMessage());

        }
    }

    /**
     * Return Customers
     */
    public function customerAction()
    {
        $this->logger = new ClerkLogger();

        $storeid = $this->getRequest()->getParam('store');
        $page = $this->getIntParam('page');
        $limit = $this->getIntParam('limit');
        $customers = [];

        try {

            $_customers = mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToSelect('postcode')
            ->addAttributeToSelect('city')
            ->addAttributeToSelect('email')
            ->addAttributeToFilter('store_id', $storeid)
            ->setPageSize($limit)
            ->setCurPage($page);

            foreach ($_customers as $_customer) {
                $customer = $_customer->getData();
                
                $customers[] = [
                    'id' => $customer['entity_id'],
                    'name' => $customer['firstname'] . ' ' . $customer['lastname'],
                    'email' => $customer['email'],
                ];
            }

            $this->getResponse()->setBody(json_encode($customers));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Version "customerAction"', $e->getMessage());

        }
    }

    public function pluginAction()
    {
        $this->logger = new ClerkLogger();

        try {

            $modules = Mage::getConfig()->getNode('modules')->children();
            $respponse = (array)$modules;

            $this->getResponse()->setBody(json_encode($respponse));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Plugin\'s "pluginAction"', $e->getMessage());

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

    public function pageAction()
    {

        $this->logger = new ClerkLogger();
        try {
            if (Mage::getStoreConfigFlag(self::XML_PATH_COLLECT_PAGES)) {

                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')

                    $Url = "http://" . $_SERVER['HTTP_HOST'];

                else {

                    $Url = "http://" . $_SERVER['HTTP_HOST'];

                }

                $items = array();
                $Additional_Fields = explode(',', Mage::getStoreConfig('clerk/general/pages_additional_fields'));
                $pages = Mage::getModel('cms/page')->getCollection();

                foreach ($pages as $page) {

                    $item = [];
                    $url = Mage::helper('cms/page')->getPageUrl($page->page_id);
                    $item['id'] = $page->page_id;
                    $item['type'] = 'cms page';
                    $item['url'] = $url;
                    $item['title'] = $page->title;
                    $item['text'] = $page->content;

                    if (!$this->ValidatePage($item)) {

                        continue;

                    }

                    if (!empty($Additional_Fields)) {

                        foreach ($Additional_Fields as $Additional_Field) {

                            try {

                                if ($page->{str_replace(' ', '', $Additional_Field)} != null) {

                                    $item[str_replace(' ', '', $Additional_Field)] = $page->{str_replace(' ', '', $Additional_Field)};

                                }else {

                                    continue;

                                }

                            } catch (Exception $e) {

                                continue;

                            }

                        }

                    }

                    $items[] = $item;
                }

                $this->logger->log('Pages Fetched', ['response' => json_encode($items)]);
                $this->getResponse()->setBody(json_encode($items));
            } else {

                $this->getResponse()->setBody(json_encode([]));

            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Page Synchronization "pageAction"', $e->getMessage());

        }

    }

    /**
     * @param $Page
     * @return bool
     */
    public function ValidatePage($Page) {

        foreach ($Page as $key => $content) {

            if (empty($content)) {

                return false;

            }

        }

        return true;

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
            $start_date = $this->getRequest()->getParam('start_date');
            $end_date = $this->getRequest()->getParam('end_date');
            $days = $this->getIntParam('days');

            if (Mage::getStoreConfigFlag('clerk/general/disable_order_synchronization')) {
                $this->logger->log('Order Synchronization is disabled', ['response' => '']);
                $this->getResponse()->setBody(json_encode([]));
            } else {
                $page = Mage::getModel('clerk/orderpage')->load($page, $limit, $start_date, $end_date, $days);
                $this->logger->log('Order Fetched', ['response' => '']);
                $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
                $this->getResponse()->setBody(json_encode($page->array));
            }

        } catch (Exception $e) {

            $this->logger->error('ERROR Order Synchronization "orderAction"', $e->getMessage());

        }
    }

    /**
     * This endpoint will list current cart products
     *
     */
    public function cartAction()
    {
        try {

            $cart_products = Mage::getModel('checkout/cart')->getQuote()->getAllVisibleItems();
            $cart_product_ids = array();

            foreach ($cart_products as $product) {
                if (!in_array($product->getProduct()->getId(), $cart_product_ids)) {
                    $cart_product_ids[] = $product->getProduct()->getId();
                }
            }

            $this->getResponse()->setBody(json_encode($cart_product_ids));

        } catch (Exception $e) {

            $this->logger->error('ERROR Fetching Store "cartAction"', $e->getMessage());

        }
    }

}

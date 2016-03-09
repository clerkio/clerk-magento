<?php

class Clerk_Clerk_ApiController extends Mage_Core_Controller_Front_Action
{
    protected function _construct()
    {
        // Always reply in json
        $this->getResponse()->setHeader('Content-type', 'application/json');
    }

    /*
     * This endpoint will list stores and are used to tell clerk
     * which which data to import. E.g.
     *
     *     [{
     *         "active": "1",
     *         "id": "1",
     *         "name": "English"
     *     }, {
     *         "active": "1",
     *         "id": "2",
     *         "name": "French"
     *     }, {
     *         "active": "1",
     *         "id": "3",
     *         "name": "German"
     *     }]
     *
     * */
    public function storeAction()
    {
        $data = array();
        foreach (Mage::helper('clerk')->getAllStores() as $store) {
            $data[] = array(
                'id' => $store->getId(),
                'name' => $store->getName(),
                'active' => Mage::getStoreConfig('clerk/generel/active', $store),
            );
        }
        $this->getResponse()->setBody(json_encode($data));
    }

    /* Endpoint for prodcuts, can be used either for pagination or to fetch a
     * single product. */
    public function productAction()
    {
        // Will set the store from param, e.g. ?store=2.
        $this->authenticate();

        // Handler for product endpoint. E.g.
        // http://store.com/clerk/api/product/id/24
        $id = $this->getRequest()->getParam('id');
        if (isset($id)) {
            $id = $this->getIntParam('id');
            if (Mage::helper('clerk')->isProductIdValid($id)) {
                $data = Mage::getModel('clerk/product')->load($id)->getInfo();
            } else {
                $data = array('Error' => 'Product not found');
            }
        } else {
            $page = $this->getIntParam('page');
            $limit = $this->getIntParam('limit');
            $page = Mage::getModel('clerk/productpage')->load(intval($page), $limit);
            $data = $page->array;
            $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
        }
        $this->getResponse()->setBody(json_encode($data));
    }

    /* Endpoint for category pagination */
    public function categoryAction()
    {
        $this->authenticate();
        $page = $this->getIntParam('page');
        $limit = $this->getIntParam('limit');
        $page = Mage::getModel('clerk/categorypage')->load($page, $limit);
        $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
        $this->getResponse()->setBody(json_encode($page->array));
    }

    /* Endpoint for order pagination */
    public function orderAction()
    {
        $this->authenticate();
        $page = $this->getIntParam('page');
        $limit = $this->getIntParam('limit');
        $days = $this->getIntParam('days');
        $page = Mage::getModel('clerk/orderpage')->load($page, $limit, $days);
        $this->getResponse()->setHeader('Total-Page-Count', $page->totalPages);
        $this->getResponse()->setBody(json_encode($page->array));
    }

    /* Authentication intercepter, will die() is something is wrong */
    private function authenticate()
    {
        $this->setStore();
        $this->getResponse()->setBody(json_encode(array('Error' => 'Not Authorized')));
        $input = $this->getRequest()->getHeader('CLERK-PRIVATE-KEY');
        $secret = Mage::helper('clerk')->getSetting('clerk/generel/privateapikey');
        if (empty($secret) or $input != trim($secret)) {
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

        return intval($value);
    }

    /* Sets store for App object, will die with error if store param is not
     * present or if store is found */
    private function setStore()
    {
        $storeid = $this->getRequest()->getParam('store');
        if (isset($storeid) && is_numeric($storeid)) {
            try {
                Mage::app()->getStore(intval($storeid));
                Mage::app()->setCurrentStore(intval($storeid));

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

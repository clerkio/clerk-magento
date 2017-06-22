<?php

class Clerk_Clerk_Block_SalesTracking extends Mage_Core_Block_Template
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    const XML_PATH_PUBLIC_KEY = 'clerk/general/publicapikey';

    /**
     * Get public key
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        return Mage::getStoreConfig(self::XML_PATH_PUBLIC_KEY);
    }

    /**
     * Get order increment id
     *
     * @return string
     */
    public function getIncrementId()
    {
        return $this->_getOrder()->getIncrementId();
    }

    /**
     * Get customer id
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->_getOrder()->getCustomerId();
    }

    /**
     * Get customer email
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->_getOrder()->getCustomerEmail();
    }

    /**
     * Get order products as json
     *
     * @return string
     */
    public function getProducts()
    {
        $order = $this->_getOrder();
        $products = array();

        foreach ($order->getAllVisibleItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */
            $products[] = array(
                'id' => $item->getProductId(),
                'quantity' => (int) $item->getQtyOrdered(),
                'price' => $item->getBasePriceInclTax(),
            );
        }

        return json_encode($products);
    }

    /**
     * Get last order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if (!$this->_order) {
            $incrementId = $this->_getCheckout()->getLastRealOrderId();
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        }

        return $this->_order;
    }

    /**
     * Get checkout session
     *
     * @return Mage_Checkout_Model_Session|Mage_Core_Model_Abstract
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
}
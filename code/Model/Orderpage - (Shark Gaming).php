<?php

class Clerk_Clerk_Model_Orderpage
{
    const XML_PATH_COLLECT_EMAILS = 'clerk/general/collect_emails';
    /**
     * @var int
     */
    public $totalPages;
    /**
     * @var array
     */
    public $array = array();
    /**
     * @var int
     */
    private $limit;
    /**
     * @var Mage_Sales_Model_Entity_Order_Collection
     */
    private $collection;

    /**
     * @param $page
     * @param $limit
     * @param $start_date
     * @param int $end_date
     * @param int $delta
     * @return $this
     * @throws Mage_Core_Model_Store_Exception
     */
    public function load($page, $limit, $start_date = 0, $end_date = 0, $delta = 1500)
    {
        $this->limit = $limit;
        $this->page = $page;
        $this->delta = $delta;

        if ($end_date == 0) {

            $end_date = strtotime('today +1 day');

        }

        if ($start_date == 0) {

            $start_date = strtotime('today -200 years');

        }

        $this->collection = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('store_id', Mage::app()->getStore()->getId())
            ->addFieldToFilter('status', array('neq' => 'canceled'))
            ->addFieldToFilter('created_at', array(
                'from' => date("Y-m-d", $start_date),
                'to' => date("Y-m-d", $end_date),
                'datetime' => true,))
            ->setOrder('entity_id', Varien_Db_Select::SQL_ASC)
            ->setPageSize($limit)
            ->setCurPage($page);
        $this->totalPages = $this->collection->getLastPageNumber();
        $this->fetch();

        return $this;
    }

    /**
     * Loop order collection and format individual orders
     */
    private function fetch()
    {
        foreach ($this->collection as $order) {

            $Dont_Track = [''];

            $OrderStatus = $order->getStatus();

            if (!in_array($OrderStatus, $Dont_Track)) {

                $this->array[] = $this->orderFormatter($order);

            }
        }
    }

    /**
     * Format order for sync
     *
     * @param $order
     * @return mixed
     */
    public function orderFormatter($order)
    {
        $items = array();

        foreach ($order->getItemsCollection() as $_item) {

            if ($_item->getParentItem()) {
                continue;
            }
            $item = new Varien_Object();
            $total_before_discount = $_item->getRowTotalInclTax();
            $total_with_discount =
                (float)($total_before_discount - $_item->getDiscountAmount());
            $actual_product_price =
                (float)($total_with_discount / (float)$_item->getQtyOrdered());
            $item->setId((int)$_item->getProductId());
            $item->setQuantity((float)$_item->getQtyOrdered());
            $item->setPrice($actual_product_price);

            Mage::dispatchEvent('clerkio_orderpage_format_item', array(
                    'output' => $item,
                    'item' => $_item,
                )
            );

            $items[] = $item->getData();

            if ($_item->getProduct()->getTypeID() === 'bundle') {

                $__items = $_item->getProductOptions();
                foreach ($__items['bundle_options'] as $BundleProducts) {

                    foreach ($BundleProducts['value'] as $BundleItems) {

                        try {

                            $_product = Mage::getModel('catalog/product')->loadByAttribute('name', $BundleItems['title']);

                            $item = new Varien_Object();
                            $actual_product_price = 0;
                            $item->setId((int)$_product->getId());
                            $item->setQuantity((float)$BundleItems['qty'] * $_item->getQtyOrdered());
                            $item->setPrice($actual_product_price);

                            Mage::dispatchEvent('clerkio_orderpage_format_item', array(
                                    'output' => $item,
                                    'item' => $_item,
                                )
                            );

                            $items[] = $item->getData();

                        } catch (Exception $e) {

                            continue;

                        }

                    }

                }

            }

        }

        $data = new Varien_Object();
        $data->setId($order->getIncrementId());
        $data->setCustomer((int)$order->getCustomerId());
        $data->setProducts($items);
        $data->setTime((int)strtotime($order->getCreatedAt()));

        if (Mage::getStoreConfigFlag(self::XML_PATH_COLLECT_EMAILS)) {
            $data->setEmail((string)$order->getCustomerEmail());
        }

        Mage::dispatchEvent('clerkio_orderpage_format_order', array(
                'output' => $data,
                'order' => $order,
            )
        );

        return $data->getData();
    }
}

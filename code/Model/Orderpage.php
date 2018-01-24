<?php

class Clerk_Clerk_Model_Orderpage
{
    const XML_PATH_COLLECT_EMAILS = 'clerk/general/collect_emails';

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    public $totalPages;

    /**
     * @var array
     */
    public $array = array();

    /**
     * @var Mage_Sales_Model_Entity_Order_Collection
     */
    private $collection;

    /**
     * Load order collection
     *
     * @param $page
     * @param $limit
     * @param int $delta
     * @return $this
     * @throws Mage_Core_Model_Store_Exception
     */
    public function load($page, $limit, $delta = 1500)
    {
        $this->limit = $limit;
        $this->page = $page;
        $this->delta = $delta;
        $this->collection = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('store_id', Mage::app()->getStore()->getId())
            ->addFieldToFilter('status', array('neq' => 'canceled'))
            ->addFieldToFilter('created_at', array(
                    'from' => strtotime("-{$delta} day", time()),
                    'datetime' => true, ))
            ->setOrder('entity_id', Varien_Db_Select::SQL_ASC)
            ->setPageSize($limit)
            ->setCurPage($page);
        $this->totalPages = $this->collection->getLastPageNumber();
        $this->fetch();

        return $this;
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
                (float) ($total_before_discount - $_item->getDiscountAmount());
            $actual_product_price =
                (float) ($total_with_discount / (int) $_item->getQtyOrdered());
            $item->setId((int) $_item->getProductId());
            $item->setQuantity((int) $_item->getQtyOrdered());
            $item->setPrice($actual_product_price);

            Mage::dispatchEvent('clerkio_orderpage_format_item', array(
                    'output' => $item,
                    'item' => $_item,
                )
            );

            $items[] = $item->getData();
        }

        $data = new Varien_Object();
        $data->setId($order->getIncrementId());
        $data->setCustomer((int) $order->getCustomerId());
        $data->setProducts($items);
        $data->setTime((int) strtotime($order->getCreatedAt()));

        if (Mage::getStoreConfigFlag(self::XML_PATH_COLLECT_EMAILS)) {
            $data->setEmail((string) $order->getCustomerEmail());
        }

        Mage::dispatchEvent('clerkio_orderpage_format_order', array(
                'output' => $data,
                'order' => $order,
            )
        );

        return $data->getData();
    }

    /**
     * Loop order collection and format individual orders
     */
    private function fetch()
    {
        foreach ($this->collection as $order) {
            $this->array[] = $this->orderFormatter($order);
        }
    }
}

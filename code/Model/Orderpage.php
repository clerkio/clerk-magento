<?php

class Clerk_Clerk_Model_Orderpage
{
    private $limit;
    public $totalPages;
    public $array = array();
    private $collection;

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
            ->setPageSize($limit)
            ->setCurPage($page);
        $this->totalPages = $this->collection->getLastPageNumber();
        $this->fetch();

        return $this;
    }

    private function fetch()
    {
        foreach ($this->collection as $order) {
            $items = array();

            foreach ($order->getItemsCollection() as $_item) {
                if ($_item->getParentItem()) {
                    continue;
                }
                $item = array();
                $total_before_discount = $_item->getRowTotalInclTax();
                $total_with_discount =
                    (float) ($total_before_discount - $_item->getDiscountAmount());
                $actual_product_price =
                    (float) ($total_with_discount / (int) $_item->getQtyOrdered());
                $item['id'] = (int) $_item->getProductId();
                $item['quantity'] = (int) $_item->getQtyOrdered();
                $item['price'] = $actual_product_price;
                $items[] = $item;
            }

            $data = array();
            $data['id'] = (int) $order->getIncrementId();
            $data['customer'] = (int) $order->getCustomerId();
            $data['products'] = $items;
            $data['email'] = (string) $order->getCustomerEmail();
            $data['time'] = (int) strtotime($order->getCreatedAt());
            $this->array[] = $data;
        }
    }
}

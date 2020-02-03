<?php

class Clerk_Clerk_Model_Productpage
{
    private $limit;
    public $totalPages;
    public $array = array();
    private $collection;

    public function load($page, $limit)
    {
        $this->limit = $limit;
        $this->page = $page;

        $this->collection = Mage::getResourceModel('catalog/product_collection')
            ->setOrder('entity_id', Varien_Db_Select::SQL_ASC)
            ->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH) //only include visible products
            ->setPageSize($limit)
            ->setCurPage($page)
            ->addStoreFilter();

        //Only grab products in stock
        if(!Mage::getStoreConfigFlag('clerk/general/include_out_of_stock_products')) {
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($this->collection);
        }
        Mage::getModel('cataloginventory/stock_status')->addStockStatusToProducts($this->collection);

        $this->totalPages = $this->collection->getLastPageNumber();
        $this->fetch();

        return $this;
    }

    private function fetch()
    {
        foreach ($this->collection as $_product) {
            $productId = $_product->getId();
            $product = Mage::getModel('clerk/product')->load($productId);

            if (!$product->isExcluded()) {
                $this->array[] = $product->getClerkExportData();
            }
        }
    }
}

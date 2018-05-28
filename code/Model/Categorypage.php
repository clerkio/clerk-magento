<?php

class Clerk_Clerk_Model_Categorypage
{
    private $limit;

    public $totalPages;

    public $array = array();

    private $collection;

    public function load($page, $limit)
    {
        $this->limit = $limit;
        $this->page = $page;
        $rootId = Mage::app()->getStore()->getRootCategoryId();
        $this->collection = Mage::getModel('catalog/category')
            ->getCollection()
            ->addIsActiveFilter()
            ->addFieldToFilter('path', array('like' => "1/$rootId/%"))
            ->addAttributeToSelect('name')
            ->setOrder('entity_id', Varien_Db_Select::SQL_ASC)
            ->setPageSize($limit)
            ->setCurPage($page);

        $this->totalPages = $this->collection->getLastPageNumber();
        $this->fetch();

        return $this;
    }

    private function fetch()
    {
        foreach ($this->collection as $category) {
            /** @var Mage_Catalog_Model_Category $category */

            //Get children categories
            $children = $category->getChildrenCategories()
                ->addIsActiveFilter()
                ->getAllIds();

            $data = array(
                'id' => (int) $category->getId(),
                'name' => $category->getName(),
                'url' => $category->getUrl(),
                'subcategories' => array_map('intval', $children),
            );

            $this->array[] = $data;
        }
    }
}

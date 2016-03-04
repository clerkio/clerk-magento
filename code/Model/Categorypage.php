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
            ->setPageSize($limit)
            ->setCurPage($page);
        $this->totalPages = $this->collection->getLastPageNumber();
        $this->fetch();

        return $this;
    }

    private function fetch()
    {
        $rootId = Mage::app()->getStore()->getRootCategoryId();

        foreach ($this->collection as $category) {
            $category = Mage::getModel('catalog/category')->load($category->getId());
            $children = Mage::getModel('catalog/category')
                ->getCollection()
                ->addIsActiveFilter()

                ->addFieldToFilter('parent_id', array('eq' => $category->getId()));
            $childrenArray = array();
            foreach ($children as $child) {
                $childrenArray[] = $child->getId();
            }
            $data = array(
                'id' => (int) $category->getId(),
                'name' => $category->getName(),
                'url' => $category->getUrl(),
                'subcategories' => array_map('intval', $childrenArray),
            );

            $this->array[] = $data;
        }
    }
}

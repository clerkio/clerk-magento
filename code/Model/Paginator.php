<?php

/**
 * Class Clerk_Clerk_Model_Paginator
 * @see https://stackoverflow.com/questions/20197518/varien-data-collection-pagination-not-working
 */
class Clerk_Clerk_Model_Paginator implements IteratorAggregate
{
    /**
     * @var Varien_Data_Collection
     */
    private $collection;

    public function __construct(Varien_Data_Collection $collection)
    {
        $this->collection = $collection;
    }

    public function getIterator()
    {
        $collection = $this->collection;

        if (false === $size = $collection->getPageSize()) {
            return $collection;
        }

        $page = $collection->getCurPage();

        if ($page > $this->collection->getLastPageNumber()) {
            return $collection;
        }

        $offset = $size * $page - $size;

        return new LimitIterator(new IteratorIterator($collection), $offset, $size);
    }
}
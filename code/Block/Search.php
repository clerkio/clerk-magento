<?php

class Clerk_Clerk_Block_Search extends Mage_CatalogSearch_Block_Result
{
	protected function _prepareLayout()
    {
		parent::_prepareLayout();
	    $this->setTemplate('clerk/search.phtml');
    }
}
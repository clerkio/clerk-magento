<?php
class Clerk_Clerk_Adminhtml_FeedsController extends Mage_Adminhtml_Controller_Action
{
	public function runAction()
	{

		$this->getResponse()->setHeader('Content-type', 'text/json; charset=UTF-8');
		try {
			if(Mage::getModel('clerk/feed')->buildFeeds())
			{
				$this->getResponse()->setBody('true');
			}
		} catch(Exception $e) {
			$this->getResponse()->setBody($e->getMessage());
		}
	}

	public function ajaxAction()
	{
		$params = $this->getRequest()->getParams();
		$this->getResponse()->setHeader('Content-type', 'text/json; charset=UTF-8');

		if((!isset($params['store_id']) && $params['store_id']) || (!isset($params['type']) && $params['type']) || (!isset($params['page']) && $params['page'])) {
			$this->getResponse()->setBody(json_encode(array('done' => false,'error' => 'missing params or not valid')));
		}
		else  {

			try {

				if(Mage::getModel('clerk/feedAjax')->buildFeeds($params['store_id'],$params['type'],$params['page']))
				{
					$this->getResponse()->setBody(json_encode(array('done' => true)));
				}
			} catch(Exception $e) {
				$this->getResponse()->setBody(json_encode(array('done' => false,'error' => $e->getMessage())));
			}
		}
	}

}

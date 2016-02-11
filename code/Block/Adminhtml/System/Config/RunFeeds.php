<?php
class Clerk_Clerk_Block_Adminhtml_System_Config_RunFeeds extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
    	$html = '<br/>';

    	$testActionUrl = $this->getUrl('adminhtml/feeds/run');

    	$javascript = "
    		$(this).up('tr').down('td.label').setStyle({color:'#FF8D00',fontStyle:'italic',fontWeight:'bold'});
    		$(this).up('tr').down('td.label').update('".Mage::helper('clerk')->__('Build Feeds')."');
    		var self = this;
    		new Ajax.Request('$testActionUrl', {
    			method: 'post',
    			parameters: {},
    			onComplete: function(transport) {
	    			if(transport.responseText == 'true'){
	    				$(self).up('tr').down('td.label').setStyle({color:'#22C400',fontStyle:'italic',fontWeight:'bold'});
	    				$(self).up('tr').down('td.label').update('".Mage::helper('clerk')->__('Done building feeds')."');
	    			}
	    			else if(transport.responseText == 'false'){
	    				$(self).up('tr').down('td.label').setStyle({color:'#FF0000',fontStyle:'italic',fontWeight:'bold'});
	    				$(self).up('tr').down('td.label').update('".Mage::helper('clerk')->__('Cound not build feeds!')."');
	    			} else {
	    				$(self).up('tr').down('td.label').setStyle({color:'#FF0000',fontStyle:'italic',fontWeight:'bold'});
	    				$(self).up('tr').down('td.label').update('".Mage::helper('clerk')->__('Request timeout')."');
	    			}
    			},
    			onLoading : function(){
    				$(self).up('tr').down('td.label').setStyle({color:'blue',fontStyle:'italic',fontWeight:'bold'});
	    			$(self).up('tr').down('td.label').update('".Mage::helper('clerk')->__('Building feeds...<br/>Please have patience :)')."');
    			}
    		});
    	";

    	$html .= $this->getLayout()->createBlock('adminhtml/widget_button')
    		->setLabel(Mage::helper('clerk')->__('Build Feeds'))
            ->setOnClick('javascript: '.$javascript)
            ->setType('button')
            ->setClass('scalable')
            ->toHtml();

		return $html;

	}
}

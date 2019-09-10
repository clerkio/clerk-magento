<?php

class Clerk_Clerk_Block_Adminhtml_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Get Clerk extension version
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {

        $clerk_confirm = <<<CLERKJS

  <script>
  		
document.addEventListener('DOMContentLoaded', function(){
    var userPreference;
    var levelbefore = document.getElementById("clerk_log_level").value;
		document.getElementById("clerk_log_level").onchange = function(){
		    if (document.getElementById("clerk_log_level").value == 'all') {
		    if (confirm("Debug Mode should not be used in production! Are you sure you want to change logging level to Debug Mode ?") == true) {
			
		} else {
			document.getElementById("clerk_log_level").value = levelbefore;
		}
		    document.getElementById("msg").innerHTML = userPreference; 
		    }else {
		    
		    levelbefore = document.getElementById("clerk_log_level").value;
		    
		    }
		};
		    
		});
</script>
CLERKJS;

        return (string)Mage::getConfig()->getNode()->modules->Clerk_Clerk->version . $clerk_confirm;
    }
}

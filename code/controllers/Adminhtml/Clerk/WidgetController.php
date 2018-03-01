<?php

class Clerk_Clerk_Adminhtml_Clerk_WidgetController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Get Clerk content for store
     */
    public function contentAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');

        /** @var Clerk_Clerk_Model_Communicator $content */
        $contentResponse = Mage::getSingleton('clerk/communicator')->getContent($storeId);
        $contentResult = json_decode($contentResponse->getBody());

        $response = [];

        if ($contentResult) {
            $response['success'] = false;
            $response['message'] = $contentResult->message;

            if ($contentResult->status === 'ok') {
                $contents = [];

                foreach ($contentResult->contents as $content) {
                    if ($content->type !== 'html') {
                        continue;
                    }

                    $contents[] = [
                        'value' => $content->id,
                        'label' => $content->name
                    ];
                }

                //Create dropdown with contents
                $field = new Varien_Data_Form_Element_Select();
                $field->setName('parameters[content]')
                    ->setId('clerk_widget_content')
                    ->setForm(new Varien_Data_Form())
                    ->addClass('required-entry')
                    ->setValues($contents)
                    ->setLabel(Mage::helper('clerk')->__('Content'));

                $element = $this->getLayout()->createBlock('adminhtml/widget_form_renderer_fieldset_element');

                $response['success'] = true;
                $response['message'] = $element->render($field);
            }
        }

        echo json_encode($response);
    }

    /**
     * Get parameters for content
     */
    public function parametersAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        $content = $this->getRequest()->getParam('content');

        $endpoint = Mage::helper('clerk')->getEndpointForContent($storeId, $content);
        $parameters = Mage::helper('clerk')->getParametersForEndpoint($endpoint);

        $html = '';

        if (!!array_intersect(['products', 'category'], $parameters)) {
            $form = new Varien_Data_Form();
            $form->setFieldsetRenderer($this->getLayout()->createBlock('adminhtml/widget_form_renderer_fieldset'));

            $fieldset = $form->addFieldset('clerk_widget_options', [
                'legend' => Mage::helper('eav')->__('Clerk Content Options'),
                'class'     => 'fieldset-wide',
                'fieldset_container_id' => 'clerk_widget_parameters'
            ]);

            if (in_array('products', $parameters)) {
                $element = $fieldset->addField('product_id', 'label', [
                    'name'      => $form->addSuffixToName('product_id', 'parameters'),
                    'class'     => 'widget-option',
                    'label'     => Mage::helper('catalog')->__('Product')
                ]);

                $field = new Mage_Adminhtml_Block_Catalog_Product_Widget_Chooser();
                $field->setLayout($this->getLayout())
                    ->setElement($element)
                    ->setFieldsetId('clerk_widget_options')
                    ->setConfig([
                        'button' => [
                            'open' => Mage::helper('catalog')->__('Select Product...')
                        ]
                    ]);

                $field->prepareElementHtml($element);
            }

            if (in_array('category', $parameters)) {
                $element = $fieldset->addField('category_id', 'label', [
                    'name'      => $form->addSuffixToName('category_id', 'parameters'),
                    'class'     => 'widget-option',
                    'label'     => Mage::helper('catalog')->__('Category')
                ]);

                $field = new Mage_Adminhtml_Block_Catalog_Category_Widget_Chooser();
                $field->setLayout($this->getLayout())
                    ->setElement($element)
                    ->setFieldsetId('clerk_widget_options')
                    ->setConfig([
                        'button' => [
                            'open' => Mage::helper('catalog')->__('Select Category...')
                        ]
                    ]);

                $field->prepareElementHtml($element);
            }

            $html .= $form->toHtml();
        }

        echo $html;
    }
}
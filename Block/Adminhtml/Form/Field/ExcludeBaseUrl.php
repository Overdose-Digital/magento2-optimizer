<?php

namespace Overdose\MagentoOptimizer\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class ExcludeBaseUrl extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('base_url_path', ['label' => __('Base Url')]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}

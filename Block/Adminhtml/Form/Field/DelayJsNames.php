<?php

namespace Overdose\MagentoOptimizer\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class DelayJsNames
 * @package Overdose\MagentoOptimizer\Block\Adminhtml\Form\Field
 */
class DelayJsNames extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('path', ['label' => __('JS Name')]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}

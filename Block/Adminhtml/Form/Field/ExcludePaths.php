<?php

namespace Overdose\MagentoOptimizer\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class ExcludePaths
 * @package Overdose\MagentoOptimizer\Block\Adminhtml\Form\Field
 */
class ExcludePaths extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('path', ['label' => __('Path')]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}

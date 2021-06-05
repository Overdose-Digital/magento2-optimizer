<?php

namespace Overdose\MagentoOptimizer\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class ExcludeControllers
 * @package Overdose\MagentoOptimizer\Block\Adminhtml\Form\Field
 */
class ExcludeControllers extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('controller_path', ['label' => __('Controller Path')]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}

<?php
declare(strict_types=1);

namespace Overdose\MagentoOptimizer\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class ExcludeImageHtmlClass
 */
class ExcludeImageHtmlClass extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('html_class', [
            'label' => __('Class Name'),
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}

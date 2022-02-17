<?php

namespace Overdose\MagentoOptimizer\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Influence implements OptionSourceInterface
{
    const ENABLE_ALL_VALUE      = 1;
    const ENABLE_CUSTOM_VALUE   = 2;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::ENABLE_ALL_VALUE, 'label' => __('Enable For All')],
            ['value' => self::ENABLE_CUSTOM_VALUE, 'label' => __('Enable For Custom')],
        ];
    }
}

<?php

namespace Overdose\MagentoOptimizer\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_PATH_PREFIX = 'od_optimizer';

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param $field
     * @param null $store
     * @param string $scope
     * @return mixed
     */
    public function getConfig($field, $store = null, $scope = 'general')
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_PREFIX . '/' . $scope . '/' . $field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return ($this->getConfig('enable')) ? TRUE : FALSE;
    }

}

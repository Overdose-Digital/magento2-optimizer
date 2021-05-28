<?php

namespace Overdose\MagentoOptimizer\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_PATH_PREFIX = 'od_optimizer';
    const KEY_FIELD_EXCLUDE_CONTROLLERS = 'exclude_controllers';
    const KEY_FIELD_EXCLUDE_PATH = 'exclude_path';
    const KEY_SCOPE_MOVE_JS_BOTTOM_PAGE = 'move_js_bottom_page';
    const KEY_SCOPE_REMOVE_BASE_URL = 'remove_base_url';
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
    public function getConfig($field, $scope = 'general', $store = null)
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
    public function isModuleEnabled()
    {
        return (bool)$this->getConfig('enable');
    }

    /**
     * Check if enabled feature removing base url from DOM html
     *
     * @return bool
     */
    public function isRemoveUrlEnabled()
    {
        return (bool)$this->getConfig('enable', 'remove_base_url');
    }

    /**
     * Check if enabled feature moving js to the bottom of page
     *
     * @return bool
     */
    public function isMoveJsEnabled()
    {
        return (bool)$this->getConfig('enable', 'move_js_bottom_page');
    }

}

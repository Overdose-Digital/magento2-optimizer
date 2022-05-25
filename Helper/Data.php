<?php

namespace Overdose\MagentoOptimizer\Helper;

use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_PATH_PREFIX = 'od_optimizer';
    const KEY_FIELD_EXCLUDE_CONTROLLERS = 'exclude_controllers';
    const KEY_FIELD_EXCLUDE_PATH = 'exclude_paths';
    const KEY_SCOPE_MOVE_JS_BOTTOM_PAGE = 'move_js_bottom_page';
    const KEY_SCOPE_REMOVE_BASE_URL = 'remove_base_url';
    const KEY_SCOPE_LAZY_LOAD_IMAGE = 'lazy_load_image';
    const KEY_FIELD_EXCLUDE_IMAGE_HTML_CLASS = 'exclude_image_html_class';

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
    public function getConfig($field, $scope, $store = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_PREFIX . '/' . $scope . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if enabled feature removing base url from DOM html
     *
     * @return bool
     */
    public function isRemoveUrlEnabled()
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_PREFIX . '/' . self::KEY_SCOPE_REMOVE_BASE_URL . '/enable',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if enabled feature moving js to the bottom of page
     *
     * @return bool
     */
    public function isMoveJsEnabled()
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_PREFIX . '/' . self::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE . '/enable',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if enabled feature "use default html attribute loading="lazy" for images"
     *
     * @return bool
     */
    public function isLazyLoadImageEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_PREFIX . '/' . self::KEY_SCOPE_LAZY_LOAD_IMAGE . '/enable',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getLazyLoadExcludeImageHtmlClassSerialized()
    {
        $path = implode('/', [
            self::CONFIG_PATH_PREFIX,
            self::KEY_SCOPE_LAZY_LOAD_IMAGE,
            self::KEY_FIELD_EXCLUDE_IMAGE_HTML_CLASS,
        ]);

        return (string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }
}

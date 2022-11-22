<?php

namespace Overdose\MagentoOptimizer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Overdose\MagentoOptimizer\Model\Config\Source\Influence;

class Data extends AbstractHelper
{
    const CONFIG_PATH_PREFIX                = 'od_optimizer';
    const KEY_FIELD_EXCLUDE_CONTROLLERS     = 'exclude_controllers';
    const KEY_FIELD_EXCLUDE_PATH            = 'exclude_paths';
    const KEY_SCOPE_MOVE_JS_BOTTOM_PAGE     = 'move_js_bottom_page';
    const KEY_SCOPE_REMOVE_BASE_URL         = 'remove_base_url';
    const KEY_SCOPE_JS_LOAD_DELAY           = 'js_load_delay';
    const KEY_FIELD_ENABLE                  = 'enable';
    const KEY_FIELD_TIMEOUT_DELAY           = 'timeout_delay';
    const KEY_FIELD_INFLUENCE               = 'influence';
    const KEY_FIELD_INFLUENCE_EXCLUDE       = 'influence_exclude';
    const KEY_FIELD_INFLUENCE_INCLUDE       = 'influence_include';
    const KEY_SCOPE_LAZY_LOAD_IMAGE         = 'lazy_load_image';
    const KEY_FIELD_EXCLUDE_IMAGE_HTML_CLASS = 'exclude_image_html_class';

    /**
     * Data constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param string $field
     * @param string $scope
     * @param null $store
     * @return mixed
     */
    public function getConfig(string $field, string $scope, $store = null)
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
    public function isRemoveUrlEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_PREFIX . '/' . self::KEY_SCOPE_REMOVE_BASE_URL . '/' . self::KEY_FIELD_ENABLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if enabled feature moving js to the bottom of page
     *
     * @return bool
     */
    public function isMoveJsEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_PREFIX . '/' . self::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE . '/' . self::KEY_FIELD_ENABLE,
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
        return $this->scopeConfig->isSetFlag(
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
    
    /**
     * Check if enabled feature add load delay to js
     *
     * @return bool
     */
    public function isJsLoadDelayEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_PREFIX . '/' . self::KEY_SCOPE_JS_LOAD_DELAY . '/' . self::KEY_FIELD_ENABLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getJsLoadDelayTimeout()
    {
        return $this->getConfig(self::KEY_FIELD_TIMEOUT_DELAY, self::KEY_SCOPE_JS_LOAD_DELAY);
    }

    /**
     * @return string
     */
    public function getJsDelayExcludedFiles(): string
    {
        $excludedJsFiles = '';

        if ($this->getJsDelayInfluenceMode() == Influence::ENABLE_ALL_VALUE) {
            $excludedJsFiles = $this->getConfig(
                self::KEY_FIELD_INFLUENCE_EXCLUDE,
                self::KEY_SCOPE_JS_LOAD_DELAY
            );
        }

        return $excludedJsFiles;
    }

    /**
     * @return string
     */
    public function getJsDelayIncludedFiles(): string
    {
        $includedJsFiles = '';

        if ($this->getJsDelayInfluenceMode() == Influence::ENABLE_CUSTOM_VALUE) {
            $includedJsFiles = $this->getConfig(
                self::KEY_FIELD_INFLUENCE_INCLUDE,
                self::KEY_SCOPE_JS_LOAD_DELAY
            );
        }

        return $includedJsFiles;
    }

    /**
     * @return int
     */
    public function getJsDelayInfluenceMode(): int
    {
        return (int) $this->getConfig(self::KEY_FIELD_INFLUENCE, self::KEY_SCOPE_JS_LOAD_DELAY);
    }
}

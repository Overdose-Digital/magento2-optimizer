<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Overdose\MagentoOptimizer\Helper\Data;

class AbstractObserver
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;


    /**
     * AbstractObserver constructor.
     * @param Data $dataHelper
     * @param SerializerInterface $serializer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $dataHelper,
        SerializerInterface $serializer,
        StoreManagerInterface $storeManager
    ) {
        $this->dataHelper = $dataHelper;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $request
     * @param $field
     * @param $scope
     * @return bool
     */
    public function checkControllersIfExcluded($request, $field, $scope)
    {
        $isSet = $this->dataHelper->getConfig($field, $scope);
        if ($isSet) {
            $excludedControllers = $this->serializer->unserialize($isSet);
        }
        if (!empty($excludedControllers)) {
            $currentPath = $request->getModuleName() . '_' .
                $request->getControllerName() . '_' .
                $request->getActionName();
            foreach ($excludedControllers as $controller) {
                if (trim($currentPath) === trim($controller['controller_path'])) {
                    return false;
                }
            }
            unset($controller);
        }

        return true;
    }

    /**
     * @param $request
     * @param $field
     * @param $scope
     * @return bool
     */
    public function checkPathIfExcluded($request, $field, $scope)
    {
        $isSet = $this->dataHelper->getConfig($field, $scope);
        if ($isSet) {
            $excludePaths = $this->serializer->unserialize($isSet);
        }
        if (!empty($excludePaths)) {
            $requestUri = $request->getRequestUri();
            foreach ($excludePaths as $path) {
                if (trim($requestUri) === trim($path['path'])) {
                    return false;
                }
            }
            unset($path);
        }

        return true;
    }

    /**
     * Check if loading=lazy attribute can be added to images
     *
     * @param Http $request
     * @return bool
     */
    public function isAddLazyLoadToImage(Http $request): bool
    {
        if ($this->dataHelper->isLazyLoadImageEnabled()) {
            if ($request->isAjax()) {
                return false;
            }

            if (!$this->checkControllersIfExcluded(
                $request,
                Data::KEY_FIELD_EXCLUDE_CONTROLLERS,
                Data::KEY_SCOPE_LAZY_LOAD_IMAGE
            )) {
                return false;
            }

            if (!$this->checkPathIfExcluded(
                $request,
                Data::KEY_FIELD_EXCLUDE_PATH,
                Data::KEY_SCOPE_LAZY_LOAD_IMAGE
            )) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @return string[]
     */
    public function getLazyLoadExcludeImageHtmlClass(): array
    {
        try {
            $value = $this->serializer->unserialize(
                $this->dataHelper->getLazyLoadExcludeImageHtmlClassSerialized()
            );
        } catch (\Exception $e) {
            $value = null;
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $row) {
            if ($htmlClass = $row['html_class'] ?? null) {
                $result[] = $htmlClass;
            }
        }

        return array_unique(array_filter($result));
    }
}

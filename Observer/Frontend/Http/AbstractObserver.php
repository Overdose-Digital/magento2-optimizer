<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

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
     * @param string $skipJssString
     * @return array
     */
    public function excludeJsString2Array($skipJssString)
    {
        $skipJssString = $this->dataHelper->getExcludedMoveFiles();
        $skipJssArray = [];
        if (!empty($skipJssString)) {
            foreach ($this->serializer->unserialize($skipJssString) as $skip) {
                if (isset($skip['path']) && !empty($skip['path'])) {
                    $skipJssArray[] = $skip['path'];
                }
            }
        }
        return $skipJssArray;
    }
}

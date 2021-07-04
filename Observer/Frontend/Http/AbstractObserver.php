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
            $excluded_controllers = $this->serializer->unserialize($isSet);
        }
        if (!empty($excluded_controllers)) {
            $current_path = $request->getModuleName() . '_' .
                $request->getControllerName() . '_' .
                $request->getActionName();
            foreach ($excluded_controllers as $excluded_controller) {
                if (trim($current_path) === trim($excluded_controller['controller_path'])) {
                    return false;
                }
            }
            unset($excluded_controller);
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
            $exclude_paths = $this->serializer->unserialize($isSet);
        }
        if (!empty($exclude_paths)) {
            $request_uri = $request->getRequestUri();
            foreach ($exclude_paths as $exclude_path) {
                if (trim($request_uri) === trim($exclude_path['path'])) {
                    return false;
                }
            }
            unset($exclude_path);
        }

        return true;
    }
}

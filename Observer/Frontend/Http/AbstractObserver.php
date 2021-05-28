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
    public $serializer;

    /**
     * @var Data
     */
    public $dataHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;


    /**
     * ResponseSendBeforeOptimizeJS constructor.
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
     * @param $observer
     * @param $field
     * @param $scope
     * @return bool
     */
    public function checkControllersIfExcluded($observer, $field, $scope)
    {
        $excluded_controllers = $this->serializer->unserialize($this->dataHelper->getConfig($field, $scope));
        if (!empty($excluded_controllers)) {
            $current_path = $observer->getRequest()->getModuleName() . '_' .
                $observer->getRequest()->getControllerName() . '_' .
                $observer->getRequest()->getActionName();
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
     * @param $observer
     * @param $field
     * @param $scope
     * @return bool
     */
    public function checkPathIfExcluded($observer, $field, $scope)
    {
        $exclude_paths = $this->serializer->unserialize($this->dataHelper->getConfig($field, $scope));
        if (!empty($exclude_paths)) {
            $request_uri = $observer->getRequest()->getRequestUri();
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

<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Magento\Framework\Event\Observer;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Overdose\MagentoOptimizer\Helper\Data;

class ResponseSendBeforeRemoveUrl extends AbstractObserver implements \Magento\Framework\Event\ObserverInterface
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
        parent::__construct($dataHelper, $serializer, $storeManager);
    }

    /**
     * @param Observer $observer
     * @return false
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->dataHelper->isModuleEnabled()) {
            /** @var $request \Magento\Framework\App\Request\Http */
            $request = $observer->getEvent()->getRequest();
            if ($request->isAjax()) {
                return false;
            }

            if ($this->dataHelper->isRemoveUrlEnabled()) {
                if (! $this->checkControllersIfExcluded($observer, $this->dataHelper::KEY_FIELD_EXCLUDE_CONTROLLERS, $this->dataHelper::KEY_SCOPE_REMOVE_BASE_URL)) {
                    return false;
                }

                if (! $this->checkPathIfExcluded($observer, $this->dataHelper::KEY_FIELD_EXCLUDE_PATH, $this->dataHelper::KEY_SCOPE_REMOVE_BASE_URL)) {
                    return false;
                }
                //remove base url
                $response = $observer->getEvent()->getResponse();
                $html = $response->getContent();
                $response->setContent($this->removeBaseUrlFromBody($html));
            }
        }
    }

    /**
     * @param $body
     * @return array|string|string[]|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function removeBaseUrlFromBody($body)
    {
        $baseUrl = substr($this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB), 0, -1);
        return $baseUrl ? preg_replace("#$baseUrl#", '', $body) : $body;
    }
}

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
     * @param Observer $observer
     * @return false
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->dataHelper->moduleIsSetFlag()) {
            /** @var $request \Magento\Framework\App\Request\Http */
            $request = $observer->getEvent()->getRequest();

            if ($request->isAjax()) {
                return false;
            }

            if ($this->dataHelper->removeUrlIsSetFlag()) {
                if (!$this->checkControllersIfExcluded($observer, Data::KEY_FIELD_EXCLUDE_CONTROLLERS, Data::KEY_SCOPE_REMOVE_BASE_URL)) {
                    return false;
                }

                if (!$this->checkPathIfExcluded($observer, Data::KEY_FIELD_EXCLUDE_PATH, Data::KEY_SCOPE_REMOVE_BASE_URL)) {
                    return false;
                }
                //remove base url
                $this->removeBaseUrlFromBody($observer);
            }
        }
    }

    /**
     * @param $body
     * @return array|string|string[]|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function removeBaseUrlFromBody($observer)
    {
        $response = $observer->getEvent()->getResponse();
        $html = $response->getContent();
        $baseUrl = substr($this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB), 0, -1);
        $baseUrl ? $response->setContent(preg_replace("#$baseUrl#", '', $html)) : $response->setContent($html);
    }
}

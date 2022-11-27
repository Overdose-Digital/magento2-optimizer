<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Overdose\MagentoOptimizer\Helper\Data;

class RemoveUrl extends AbstractObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @param Observer $observer
     * @return false
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->dataHelper->isRemoveUrlEnabled()) {
            /** @var $request \Magento\Framework\App\Request\Http */
            $request = $observer->getEvent()->getRequest();
            if ($request->isAjax()) {
                return false;
            }

            if (!$this->checkControllersIfExcluded($request, Data::KEY_FIELD_EXCLUDE_CONTROLLERS, Data::KEY_SCOPE_REMOVE_BASE_URL)) {
                return false;
            }

            if (!$this->checkPathIfExcluded($request, Data::KEY_FIELD_EXCLUDE_PATH, Data::KEY_SCOPE_REMOVE_BASE_URL)) {
                return false;
            }
            //remove base url
            $this->removeBaseUrlFromBody($observer);
        }
    }

    /**
     * @param $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function removeBaseUrlFromBody($observer)
    {
        $response = $observer->getEvent()->getResponse();
        $html = $response->getContent();
        $baseUrl = substr($this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB), 0, -1);
        $baseUrl ? $response->setContent(preg_replace("#$baseUrl#", '', $html)) : $response->setContent($html);
    }
}

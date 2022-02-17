<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Magento\Framework\Event\ObserverInterface;
use Overdose\MagentoOptimizer\Helper\Data;
use Magento\Framework\Event\Observer;

class ResponseSendBeforeOptimizeJS extends AbstractObserver implements ObserverInterface
{
    const JS_LOOK_FOR_STRING = '#(<script *\b(?!nodefer)\b\S+.*?<\/script>)#is';

    /**
     * @param Observer $observer
     * @return false
     */
    public function execute(Observer $observer)
    {
        if ($this->dataHelper->isMoveJsEnabled()) {
            /** @var $request \Magento\Framework\App\Request\Http */
            $request = $observer->getEvent()->getRequest();
            if ($request->isAjax()) {
                return false;
            }

            if (!$this->checkControllersIfExcluded(
                $request,
                Data::KEY_FIELD_EXCLUDE_CONTROLLERS,
                Data::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE
            )) {
                return false;
            }

            if (!$this->checkPathIfExcluded(
                $request,
                Data::KEY_FIELD_EXCLUDE_PATH,
                Data::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE
            )) {
                return false;
            }
            //move js to the bottom page
            $this->moveJs($observer);
        }
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function moveJs(Observer $observer)
    {
        $deferredJs = '';
        $response = $observer->getEvent()->getResponse();
        $html = preg_replace_callback(
            static::JS_LOOK_FOR_STRING,
            function ($script) use (&$deferredJs) {
                $deferredJs .= $script[0];

                return '';
            },
            $response->getContent()
        );
        $html = str_replace('</body', $deferredJs . '</body', $html);
        $response->setContent($html);
    }
}

<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\Event\ObserverInterface;
use Overdose\MagentoOptimizer\Helper\Data;
use Magento\Framework\Event\Observer;

/**
 * Class ResponseSendBeforeOptimizeJS
 */
class ResponseSendBeforeOptimizeJS extends AbstractObserver implements ObserverInterface
{
    const JS_LOOK_FOR_STRING = '#(<script *\b(?!nodefer)\b\S+?(.*?)<\/script>)#is';

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->dataHelper->isMoveJsEnabled()) {
            return;
        }

        /** @var RequestHttp|null $request */
        $request = $observer->getEvent()->getData('request');

        if ($request->isAjax()
            || !$this->checkControllersIfExcluded($request, Data::KEY_FIELD_EXCLUDE_CONTROLLERS, Data::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE)
            || !$this->checkPathIfExcluded($request, Data::KEY_FIELD_EXCLUDE_PATH, Data::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE)
        ) {
            return;
        }

        $this->moveJs($observer);
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function moveJs(Observer $observer)
    {
        /** @var ResponseHttp|null $response */
        $response = $observer->getEvent()->getData('response');

        if (!$response) {
            return;
        }

        $deferredJs = '';
        $html = preg_replace_callback(
            static::JS_LOOK_FOR_STRING,
            static function ($script) use (&$deferredJs) {
                $deferredJs .= $script[0];

                return '';
            },
            $response->getContent()
        );
        $html = str_replace('</body', $deferredJs . '</body', $html);
        $response->setContent($html);
    }
}

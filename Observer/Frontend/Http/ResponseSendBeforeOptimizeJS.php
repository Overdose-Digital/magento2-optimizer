<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Overdose\MagentoOptimizer\Helper\Data;
use Magento\Framework\Event\Observer;

class ResponseSendBeforeOptimizeJS extends AbstractObserver implements \Magento\Framework\Event\ObserverInterface
{
    const JS_LOOK_FOR_STRING = '#(<script *\b(?!nodefer)\b\S+.*?<\/script>)#is';
    //const JS_LOOK_FOR_STRING = '#(<script.*?</script>)#is';

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

            if (!$this->checkControllersIfExcluded($request, Data::KEY_FIELD_EXCLUDE_CONTROLLERS, Data::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE)) {
                return false;
            }

            if (!$this->checkPathIfExcluded($request, Data::KEY_FIELD_EXCLUDE_PATH, Data::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE)) {
                return false;
            }
            //move js to the bottom page
            $this->moveJs($observer);
        }
    }

    /**
     * @param $observer
     */
    public function moveJs($observer)
    {
        $response = $observer->getEvent()->getResponse();
        $html = $response->getContent();

        preg_match_all(static::JS_LOOK_FOR_STRING, $html, $matches);
        $js = '';

        foreach ($matches[0] as $value) {
            $js .= $value;
        }

        $html = preg_replace(static::JS_LOOK_FOR_STRING, '', $html);
        $html = preg_replace('#</body>#', $js.'</body>', $html);

        $response->setContent($html);
    }
}

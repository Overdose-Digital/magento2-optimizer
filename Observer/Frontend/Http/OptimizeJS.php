<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Overdose\MagentoOptimizer\Helper\Data;

class OptimizeJS extends AbstractObserver implements ObserverInterface
{
    const JS_LOOK_FOR_STRING = '#<script(?=\s|>)(?!(?:[^>=]|=)*?\snodefer)[^>]*>.*?</script>#is';
    const JS_LOOK_FOR_COMMENTED_SCRIPT = '#<!--.*?-->#is';

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->dataHelper->isMoveJsEnabled()) {
            return;
        }

        /** @var \Magento\Framework\App\Request\Http|null $request */
        $request = $observer->getEvent()->getData('request');

        if ($request->isAjax()
            || !$this->checkControllersIfExcluded($request, Data::KEY_FIELD_EXCLUDE_CONTROLLERS, Data::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE)
            || !$this->checkPathIfExcluded($request, Data::KEY_FIELD_EXCLUDE_PATH, Data::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE)
        ) {
            return;
        }

        //move js to the bottom page
        $this->moveJs($observer);
    }

    /**
     * Match all <script*> tags: inline, not inline, template, x-magento-init.
     * Skip tags with "nodefer" attribute.
     * @param Observer $observer
     * @return void
     */
    public function moveJs(Observer $observer)
    {
        /** @var \Magento\Framework\App\Response\Http|null $response */
        $response = $observer->getEvent()->getData('response');

        if (!$response) {
            return;
        }

        $skipJss = $this->excludeJsString2Array($this->dataHelper->getExcludedMoveFiles());
        $deferredJs = '';
        $html = $this->removeCommentContainingScript($response->getContent());
        $html = preg_replace_callback(
            static::JS_LOOK_FOR_STRING,
            static function ($script) use ($skipJss, &$deferredJs) {
                $skip = false;
                foreach ($skipJss as $js) {
                    if (strpos($script[0], $js)) {
                        $skip = true;
                        break;
                    }
                }
                if (!$skip) {
                    $deferredJs .= $script[0];
                    return '';
                } else {
                    return $script[0];
                }
            },
            $html
        );
        $html = str_replace('</body', $deferredJs . '</body', $html??'');
        $response->setContent($html);
    }

    /**
     * @param string $html
     * @return string
     */
    private function removeCommentContainingScript($html): string
    {
        $stripped = preg_replace_callback(
            static::JS_LOOK_FOR_COMMENTED_SCRIPT,
            static function ($htmlComment) {
                if (strpos($htmlComment[0], '<script') !== false) {
                    return '<!---->';
                }
                return $htmlComment[0];
            },
            $html
        );
        return is_null($stripped) ? $html : $stripped;
    }
}

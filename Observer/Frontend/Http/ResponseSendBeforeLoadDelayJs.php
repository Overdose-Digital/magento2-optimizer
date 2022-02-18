<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Overdose\MagentoOptimizer\Helper\Data;

/**
 * class ResponseSendBeforeLoadDelayJs
 */
class ResponseSendBeforeLoadDelayJs extends AbstractObserver implements ObserverInterface
{
    const JS_LOOK_FOR_SCRIPT_STRING         = '@(<script[^<>]*>)(.*)</script>@msU';
    const JS_LOOK_FOR_SCRIPT_STRING_SRC     = '@src="([^"]+)"@i';
    const MODE_VARIANT_ALL                  = 0;
    const MODE_VARIANT_INCLUDED             = 1;
    const MODE_VARIANT_EXCLUDED             = 2;

    /**
     * @param Observer $observer
     * @return bool
     */
    public function execute(Observer $observer)
    {
        if ($this->dataHelper->isJsLoadDelayEnabled() && $this->dataHelper->getJsLoadDelayTimeout()) {
            /** @var $request Http */
            $request = $observer->getEvent()->getRequest();
            $jsFilePaths = [];
            $mode = self::MODE_VARIANT_ALL;

            if ($request->isAjax()) {
                return false;
            }

            if (!empty($this->dataHelper->getJsDelayExcludedFiles())) {
                $jsFiles = $this->dataHelper->getJsDelayExcludedFiles();
                $mode = self::MODE_VARIANT_EXCLUDED;
            }

            if (!empty($this->dataHelper->getJsDelayIncludedFiles())) {
                $jsFiles = $this->dataHelper->getJsDelayIncludedFiles();
                $mode = self::MODE_VARIANT_INCLUDED;
            }

            if (!empty($jsFiles)) {
                foreach ($this->serializer->unserialize($jsFiles) as $file) {
                    if (isset($file['path']) && !empty($file['path'])) {
                        $jsFilePaths[] = $file['path'];
                    }
                }
            }

            if (!$this->checkControllersIfExcluded(
                $request,
                Data::KEY_FIELD_EXCLUDE_CONTROLLERS,
                Data::KEY_SCOPE_JS_LOAD_DELAY
            )) {
                return false;
            }

            if (!$this->checkPathIfExcluded(
                $request,
                Data::KEY_FIELD_EXCLUDE_PATH,
                Data::KEY_SCOPE_JS_LOAD_DELAY
            )) {
                return false;
            }

            $this->addDelayToJs($observer, $jsFilePaths, $mode);
        }

        return true;
    }

    /**
     * @param Observer $observer
     * @param array $jsFilePaths
     * @param int $mode
     * @return void
     */
    public function addDelayToJs(Observer $observer, array $jsFilePaths, int $mode)
    {
        $response = $observer->getEvent()->getResponse();
        $delayValue = (int)$this->dataHelper->getJsLoadDelayTimeout();
        $html = $response->getContent();

        if (!$delayValue || (empty($jsFilePaths) && $mode !== self::MODE_VARIANT_ALL)) {
            return;
        }

        $delayedJs = $this->getExternalScripts($html);

        if (empty($delayedJs)) {
            return;
        }

        $srcWillDelay = [];

        foreach ($delayedJs as $script) {
            if ($mode == self::MODE_VARIANT_ALL) {
                $srcWillDelay[] = $script['url'];
                $html = str_replace($script['row'], '', $html);
            } else {
                foreach ($jsFilePaths as $path) {
                    if ($mode == self::MODE_VARIANT_INCLUDED) {
                        if (strpos($script['url'], $path) !== false) {
                            $srcWillDelay[] = $script['url'];
                            $html = str_replace($script['row'], '', $html);
                        }
                    } else {
                        if (strpos($script['url'], $path) !== false) {
                            break;
                        } else {
                            $srcWillDelay[] = $script['url'];
                            $html = str_replace($script['row'], '', $html);
                        }
                    }
                }
            }
        }

        if (!empty($srcWillDelay)) {
            $result = $this->createJsScriptFunc($srcWillDelay);
            $html = str_replace('</body', $result . '</body', $html);
        }

        $response->setContent($html);
    }

    /**
     * @param array $src
     * @return string
     */
    private function createJsScriptFunc(array $src)
    {
        $result = '';

        if ($src) {
            $delayValue = (int)$this->dataHelper->getJsLoadDelayTimeout();
            $srcRow = json_encode($src);

            $result = '<script type="text/javascript">
                            document.addEventListener("DOMContentLoaded", function() {
                                setTimeout(function() {
                                       var headerEl = document.getElementsByTagName("head")[0];
                                       const srcArr =' . $srcRow .';
                                       for (let el of srcArr) {
                                           var script = document.createElement("script");
                                           script.type = "text/javascript";
                                           script.src = el;
                                           headerEl.appendChild(script);
                                       }
                                }, '. ($delayValue * 1000) .');
                            });
                        </script>';
        }

        return $result;
    }

    /**
     * @param string $html
     * @return array
     */
    private function getExternalScripts(string $html)
    {
        $pattern = self::JS_LOOK_FOR_SCRIPT_STRING;
        $externalJsScripts  = [];

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (preg_match(self::JS_LOOK_FOR_SCRIPT_STRING_SRC, $match[1], $srcMatches)) {
                    $externalJsScripts[] = ['row' => $match[0], 'url' => $srcMatches[1]];
                }
            }
        }

        return $externalJsScripts;
    }
}

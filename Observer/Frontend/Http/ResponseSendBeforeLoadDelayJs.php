<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Overdose\MagentoOptimizer\Helper\Data;
use Overdose\MagentoOptimizer\Model\Config\Source\Influence;

/**
 * class ResponseSendBeforeLoadDelayJs
 */
class ResponseSendBeforeLoadDelayJs extends AbstractObserver implements ObserverInterface
{
    const JS_LOOK_FOR_SCRIPT_STRING         = '@(<script[^<>]*>)(.*)</script>@msU';
    const JS_LOOK_FOR_SCRIPT_STRING_SRC     = '@src="([^"]+)"@i';

    /**
     * @var $jsLoadDelayTimeout
     */
    private $jsLoadDelayTimeout = null;

    /**
     * @param Observer $observer
     * @return bool
     */
    public function execute(Observer $observer): bool
    {
        $this->jsLoadDelayTimeout = $this->dataHelper->getJsLoadDelayTimeout();

        if ($this->dataHelper->isJsLoadDelayEnabled() && $this->jsLoadDelayTimeout) {
            /** @var $request Http */
            $request = $observer->getEvent()->getRequest();
            $jsFilePaths = [];

            if ($request->isAjax()) {
                return false;
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

            switch ($this->dataHelper->getJsDelayInfluenceMode()) {
                case Influence::ENABLE_ALL_VALUE:
                    $jsFiles = $this->dataHelper->getJsDelayExcludedFiles();
                break;
                case Influence::ENABLE_CUSTOM_VALUE:
                    $jsFiles = $this->dataHelper->getJsDelayIncludedFiles();
                break;
            }

            if (!empty($jsFiles)) {
                foreach ($this->serializer->unserialize($jsFiles) as $file) {
                    if (isset($file['path']) && !empty($file['path'])) {
                        $jsFilePaths[] = $file['path'];
                    }
                }
            }

            $this->addDelayToJs($observer, $jsFilePaths);
        }

        return true;
    }

    /**
     * @param Observer $observer
     * @param array $jsFilePaths
     * @return void
     */
    public function addDelayToJs(Observer $observer, array $jsFilePaths)
    {
        $response = $observer->getEvent()->getResponse();
        $html = $response->getContent();
        $influenceMode = $this->dataHelper->getJsDelayInfluenceMode();

        if (!$this->jsLoadDelayTimeout) {
            return;
        }

        $delayedJs = $this->getExternalScripts($html);

        if (empty($delayedJs)) {
            return;
        }

        $srcWillDelay = [];

        foreach ($delayedJs as $script) {
            if ($influenceMode == Influence::ENABLE_ALL_VALUE) {
                if (!empty($jsFilePaths)) {
                    foreach ($jsFilePaths as $path) {
                        if (strpos($script['url'], $path) !== false) {
                            break;
                        } else {
                            $srcWillDelay[] = $script['url'];
                            $html = str_replace($script['row'], '', $html);
                        }
                    }
                } else {
                    $srcWillDelay[] = $script['url'];
                    $html = str_replace($script['row'], '', $html);
                }
            } else {
                if (!empty($jsFilePaths)) {
                    foreach ($jsFilePaths as $path) {
                        if (strpos($script['url'], $path) !== false) {
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
    private function createJsScriptFunc(array $src): string
    {
        $result = '';

        if ($src) {
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
                                }, '. ((int) $this->jsLoadDelayTimeout * 1000) .');
                            });
                        </script>';
        }

        return $result;
    }

    /**
     * @param string $html
     * @return array
     */
    private function getExternalScripts(string $html): array
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

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
class LoadDelayJs extends AbstractObserver implements ObserverInterface
{
    const JS_LOOK_FOR_SCRIPT_STRING     = '@<script(?=\s|>)(?!(?:[^>=]|=)*?\snolazy)[^>]*>.*?</script>@si';
    const JS_LOOK_FOR_SCRIPT_STRING_SRC = '@src="([^"]+)"@i';

    /**
     * @var $jsLoadDelayTimeout
     */
    private $jsLoadDelayTimeout = null;

    private $delayedJs = [
        'files' => [],
        'inline' => []
    ];

    /**
     * Main call structure.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->jsLoadDelayTimeout = $this->dataHelper->getJsLoadDelayTimeout();

        if ($this->isDoDelay($observer)) {
            switch ($this->dataHelper->getJsDelayInfluenceMode()) {
                case Influence::ENABLE_ALL_VALUE:
                    $jsFiles = $this->dataHelper->getJsDelayExcludedFiles();
                    break;
                case Influence::ENABLE_CUSTOM_VALUE:
                    $jsFiles = $this->dataHelper->getJsDelayIncludedFiles();
                    break;
            }

            $jsFilePaths = [];
            if (!empty($jsFiles)) {
                foreach ($this->serializer->unserialize($jsFiles) as $file) {
                    if (isset($file['path']) && !empty($file['path'])) {
                        $jsFilePaths[] = $file['path'];
                    }
                }
            }

            $this->addDelayToJs($observer, $jsFilePaths);
        }
    }

    /**
     * @param $observer
     * @return void
     */
    public function isDoDelay($observer)
    {
        if (!$this->dataHelper->isJsLoadDelayEnabled()) {
            return false;
        }

        if (!$this->jsLoadDelayTimeout) {
            return false;
        }

        /** @var $request Http */
        $request = $observer->getEvent()->getRequest();

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

        return true;
    }

    /**
     * Replace JS in HTML.
     *
     * @param Observer $observer
     * @param array $jsFilePaths
     * @return void
     */
    private function addDelayToJs(Observer $observer, array $jsFilePaths)
    {
        $response = $observer->getEvent()->getResponse();
        $html = $response->getContent();

        $this->getDelayScripts($html);
        $html = $this->checkReplaceSrcJs($html, $jsFilePaths);
        $html = $this->checkReplaceInlineJs($html);

        $response->setContent($html);
    }

    /**
     * Match all <script*> tags: inline, not inline, template, x-magento-init.
     * Skip tags with "nolazy" attribute.
     * Collect data to class's parameter.
     *
     * @param string $html
     * @return void
     */
    private function getDelayScripts(string $html)
    {
        $pattern = self::JS_LOOK_FOR_SCRIPT_STRING;

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (preg_match(self::JS_LOOK_FOR_SCRIPT_STRING_SRC, $match[0], $srcMatches)) {
                    $this->delayedJs['files'][] = ['row' => $match[0], 'url' => $srcMatches[1]];
                } else {
                    $this->delayedJs['inline'][] = $match[0];
                }
            }
        }
    }

    /**
     * Check configs and return page content with replaced "src" JS.
     *
     * @param string $html
     * @param array $html
     * @return string
     */
    protected function checkReplaceSrcJs($html, $jsFilePaths)
    {
        $influenceMode = $this->dataHelper->getJsDelayInfluenceMode();
        $srcWillDelay = [];

        foreach ($this->delayedJs['files'] as $script) {
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
            $result = $this->createJsSrcScript($srcWillDelay);
            $html = str_replace('</body', $result . '</body', $html);
        }

        return $html;
    }

    /**
     * JS code that will create "script" tags with "src" attribute.
     *
     * @param array $src
     * @return string
     */
    private function createJsSrcScript(array $src): string
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
     * Return page content with replaced inline JS.
     *
     * @param $html
     * @return array|mixed|string|string[]
     */
    protected function checkReplaceInlineJs($html)
    {
        if (!empty($this->delayedJs['inline'])) {
            $inlineJS = '';
            foreach ($this->delayedJs['inline'] as $inline) {
                $html = str_replace($inline, '', $html);
                $inlineJS .= $inline;
            }
//            $inlineJS = '<script>derp</script>';
            $result = '<script type="text/javascript">
                            document.addEventListener("DOMContentLoaded", function() {
                                setTimeout(function() {
                                       document.body.innerHTML += ' . json_encode($inlineJS) . ';
                                }, '. ((int) $this->jsLoadDelayTimeout * 1000) .');
                            });
                        </script>';
        }

        $html = str_replace('</body', $result . '</body', $html);

        return $html;
    }
}

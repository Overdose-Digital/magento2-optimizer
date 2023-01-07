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
    const JS_LOOK_FOR_SCRIPT_STRING = '@<script(?=\s|>)(?!(?:[^>=]|=)*?\snolazy)[^>]*>.*?</script>@si';
    const JS_LOOK_FOR_SCRIPT_SKIP   = 'var BASE_URL = ';
    const JS_LOOK_FOR_SCRIPT_DATA   = '@<script(.*?)>(.*?)</script>@is';

    /**
     * @var $jsLoadDelayTimeout
     */
    private $jsLoadDelayTimeout = null;

    private $detectedJs = [];

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
        $html = $this->checkReplaceJs($html, $jsFilePaths);

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
        if (preg_match_all(self::JS_LOOK_FOR_SCRIPT_STRING, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                // Skip important basic inline script
                if (strpos($match[0], self::JS_LOOK_FOR_SCRIPT_SKIP) === false) {
                    // get "<script " attributes and content
                    preg_match(self::JS_LOOK_FOR_SCRIPT_DATA, $match[0], $scriptData);

                    $attributes = (isset($scriptData[1])) ? trim($scriptData[1]) : '';
                    $content = (isset($scriptData[2])) ? trim($scriptData[2]) : '';

                    $this->detectedJs[] = [
                        'row' => $match[0],
                        'attributes' => $attributes,
                        'content' => $content,
                    ];
                }
            }
            unset($matches, $match, $scriptData, $attributes, $content);
        }
    }

    /**
     * Check configs and return page content with replaced "src" JS.
     *
     * @param string $html
     * @param array $html
     * @return string
     */
    protected function checkReplaceJs($html, $jsFilePaths)
    {
        $influenceMode = $this->dataHelper->getJsDelayInfluenceMode();
        $scriptsWillDelay = [];

        foreach ($this->detectedJs as $script) {
            if ($influenceMode == Influence::ENABLE_ALL_VALUE) {
                if (!empty($jsFilePaths)) {
                    foreach ($jsFilePaths as $path) {
                        if (strpos($script['attributes'], $path) !== false) {
                            break;
                        } else {
                            $scriptsWillDelay[] = $script;
                        }
                    }
                } else {
                    $scriptsWillDelay[] = $script;
                }
            } else {
                if (!empty($jsFilePaths)) {
                    foreach ($jsFilePaths as $path) {
                        if (strpos($script['attributes'], $path) !== false) {
                            $scriptsWillDelay[] = $script;
                        }
                    }
                }
            }
        }
        unset($script, $path, $jsFilePaths);

        if (!empty($scriptsWillDelay)) {
            foreach ($scriptsWillDelay as &$script) {
                // remove from a page
                $html = str_replace($script['row'], '', $html);
                // remove not needed anymore data from array
                unset($script['row']);
                // prepare array of attributes and replace string with array
                $attributes = [];
                if ($script['attributes']) {
                    foreach (array_filter(explode(' ', $script['attributes'])) as $attribute) {
                        if (strpos($attribute, '=')) {
                            list($attrName, $attrValue) = explode('=', $attribute);
                            $attrValue = trim($attrValue,'\'"');
                        } else {
                            $attrName = $attribute;
                            $attrValue = '';
                        }
                        $attributes[] = [
                            'name' => $attrName,
                            'value' => $attrValue
                        ];
                    }
                }
                $script['attributes'] = $attributes;
            }

            $result = $this->createJsScript($scriptsWillDelay);
            $html = str_replace('</body', $result . '</body', $html);
        }

        return $html;
    }

    /**
     * JS code that will create "script" tags with attributes.
     *
     * @param array $scripts
     * @return string
     */
    private function createJsScript(array $scripts): string
    {
        if (!$scripts) {
            return '';
        }

        $scripts = json_encode($scripts);

        $result = '<script type="text/javascript">
                       let rendered = false;
                       function renderScripts() {
                           if (rendered) {
                               return;
                           }
                           
                           const scripts =' . $scripts . ';
                           for (let lazyScript of scripts) {
                               var script = document.createElement("script");
                               
                               for (let attribute of lazyScript.attributes) {
                                   script.setAttribute(attribute.name, attribute.value);
                               }
                               if (lazyScript.content) {
                                   script.text = lazyScript.content;
                               }
                               
                               document.body.appendChild(script);
                           }
                       }
                       
                       document.addEventListener("DOMContentLoaded", function() {
                           setTimeout(function() {
                               renderScripts();
                           }, '. ((int) $this->jsLoadDelayTimeout * 1000) .');
                       });
                       document.addEventListener("scroll", (event) => {
                           renderScripts();
                       });
                   </script>';

        return $result;
    }
}

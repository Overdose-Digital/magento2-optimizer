<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Magento\Store\Model\StoreManagerInterface;
use Overdose\MagentoOptimizer\Helper\Data;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Event\Observer;

class ResponseSendBeforeOptimizeJS extends AbstractObserver implements \Magento\Framework\Event\ObserverInterface
{
    const JS_LOOK_FOR_STRING = '#(<script *\b(?!nodefer)\b\S+.*?<\/script>)#is';
    //const JS_LOOK_FOR_STRING = '#(<script.*?</script>)#is';

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

            if ($this->dataHelper->moveJsIsSetFlag()) {
                if (!$this->checkControllersIfExcluded($observer, Data::KEY_FIELD_EXCLUDE_CONTROLLERS, Data::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE)) {
                    return false;
                }

                if (!$this->checkPathIfExcluded($observer, Data::KEY_FIELD_EXCLUDE_PATH, Data::KEY_SCOPE_MOVE_JS_BOTTOM_PAGE)) {
                    return false;
                }
                //move js to the bottom page
                $this->moveJs($observer);
            }
        }
    }

    /**
     * @param $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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

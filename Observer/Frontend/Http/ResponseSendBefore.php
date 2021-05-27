<?php

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Overdose\MagentoOptimizer\Helper\Data;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Event\Observer;

class ResponseSendBefore implements \Magento\Framework\Event\ObserverInterface
{
    const JS_LOOK_FOR_STRING = '#(<script *\b(?!nodefer)\b\S+.*?<\/script>)#is';
    //const JS_LOOK_FOR_STRING = '#(<script.*?</script>)#is';

    /**
     * @var SerializerInterface
     */
    public $serializer;

    /**
     * @var Data
     */
    public $dataHelper;


    /**
     * ResponseSendBefore constructor.
     * @param Data $dataHelper
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Data $dataHelper,
        SerializerInterface $serializer
    ) {
        $this->dataHelper = $dataHelper;
        $this->serializer = $serializer;
    }

    public function checkControllersExceptions($observer)
    {
        $excluded_controllers = $this->serializer->unserialize($this->dataHelper->getConfig('exclude_controllers'));
        if (!empty($excluded_controllers)) {
            $current_path = $observer->getRequest()->getModuleName() . '_' .
                $observer->getRequest()->getControllerName() . '_' .
                $observer->getRequest()->getActionName();
            foreach ($excluded_controllers as $excluded_controller) {

                if (trim($current_path) === trim($excluded_controller['controller_path'])) {
                    return false;
                }
            }
            unset($excluded_controller);
        }

        return true;
    }

    public function checkPathExceptions($observer)
    {
        $exclude_paths = $this->serializer->unserialize($this->dataHelper->getConfig('exclude_path'));
        if (!empty($exclude_paths)) {
            $request_uri = $observer->getRequest()->getRequestUri();
            foreach ($exclude_paths as $exclude_path) {
                if (trim($request_uri) === trim($exclude_path['path'])) {
                    return false;
                }
            }
            unset($exclude_path);
        }

        return true;
    }

    /**
     * @param Observer $observer
     * @return false
     */
    public function execute(Observer $observer)
    {
        if ($this->dataHelper->isEnabled()) {
            /** @var $request \Magento\Framework\App\Request\Http */
            $request = $observer->getEvent()->getRequest();
            if ($request->isAjax()) {
                return false;
            }

            if (! $this->checkControllersExceptions($observer))
                return false;

            if (! $this->checkPathExceptions($observer))
                return false;

            $response = $observer->getEvent()->getResponse();
            $html = $response->getContent();

            preg_match_all(static::JS_LOOK_FOR_STRING, $html, $matches);
            $js = '';

            foreach ($matches[0] as $value) {
                $js .= $value;
            }
            $html = preg_replace(static::JS_LOOK_FOR_STRING, '', $html);
            $html = preg_replace('#</body>#',$js.'</body>', $html);

            $response->setContent($html);
        }
    }
}

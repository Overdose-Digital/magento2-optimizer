<?php
/**
 * Copyright © Overdose Digital. All rights reserved.
 * See LICENSE_OVERDOSE.txt for license details.
 */

declare(strict_types=1);

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Magento\Framework\Event\Observer;

/**
 * ResponseSendBeforeLazyLoadImage class
 *
 * Used to add loading=lazy to all images
 */
class ResponseSendBeforeLazyLoadImage extends AbstractObserver implements \Magento\Framework\Event\ObserverInterface
{
    const IMG_LAZY_LOADING_LOOK_FOR_STRING = '/<img(?=\s|>)(?!(?:[^>=]|=([\'"])(?:(?!\1).)*\1)*?\sloading=)[^>]*>/';

    /**
     * Used to add loading=lazy to all images
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->isAddLazyLoadToImage($observer->getEvent()->getRequest())) {
            $this->addLazyLoadToImage($observer);
        }
    }

    /**
     *  Add loading=lazy to images
     *
     * @param Observer $observer
     * @return void
     */
    public function addLazyLoadToImage(Observer $observer)
    {
        $response = $observer->getEvent()->getResponse();

        $newString =  preg_replace_callback(
            static::IMG_LAZY_LOADING_LOOK_FOR_STRING,
            function ($matches) {
                return str_replace(
                    '<img ',
                    stripos($matches[0], '=\\') === false
                        ? '<img loading="lazy" '
                        : '<img loading=\"lazy\" ',
                    $matches[0]
                );
            },
            $response->getContent()
        );

        $response->setContent($newString);
    }
}

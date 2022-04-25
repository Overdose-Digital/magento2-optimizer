<?php
/**
 * Copyright © Overdose Digital. All rights reserved.
 * See LICENSE_OVERDOSE.txt for license details.
 */

declare(strict_types=1);

namespace Overdose\MagentoOptimizer\Observer\Frontend\Http;

use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * ResponseSendBeforeLazyLoadImage class
 *
 * Used to add loading=lazy to all images
 */
class ResponseSendBeforeLazyLoadImage extends AbstractObserver implements ObserverInterface
{
    const IMG_LAZY_LOADING_LOOK_FOR_STRING = '/<img(?!\s+nolazy)(?=\s|>)(?!(?:[^>=]|=([\'"])(?:(?!\1).)*\1)*?\sloading=)[^>]*>/';

    /**
     * Used to add loading=lazy to all images
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var RequestHttp|null $request */
        $request = $observer->getEvent()->getData('request');

        if ($request && $this->isAddLazyLoadToImage($request)) {
            $this->addLazyLoadToImage($observer);
        }
    }

    /**
     *  Add loading=lazy to images
     *
     * @param Observer $observer
     * @return void
     */
    public function addLazyLoadToImage(Observer $observer): void
    {
        /** @var ResponseHttp|null $response */
        $response = $observer->getEvent()->getData('response');

        if (!$response) {
            return;
        }

        $content = preg_replace_callback(
            static::IMG_LAZY_LOADING_LOOK_FOR_STRING,
            static function ($matches) {
                return str_replace(
                    '<img ',
                    false === stripos($matches[0], '=\\')
                        ? '<img loading="lazy" '
                        : '<img loading=\"lazy\" ',
                    $matches[0]
                );
            },
            $response->getContent()
        );

        $response->setContent($content);
    }
}

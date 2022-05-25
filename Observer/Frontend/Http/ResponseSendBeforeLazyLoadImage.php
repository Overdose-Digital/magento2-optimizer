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
    const IMG_LAZY_LOADING_LOOK_FOR_STRING = '/<img(?=\s|>)(?!(?:[^>=]|=([\'"])(?:(?!\1).)*\1)*?(\sloading=|\snolazy[=,\s,\/]))[^>]*>/';

    const IMG_HAS_HTML_CLASS_REGEX_FORMAT = '/class=(("|"([^"]*)\s)(%s)("|\s([^"]*)")|(\'|\'([^\']*)\s)(%s)(\'|\s([^\']*)\'))/';

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

        $skipImagesPattern = $this->getSkipImageByHtmlClassPattern();
        $content = preg_replace_callback(
            static::IMG_LAZY_LOADING_LOOK_FOR_STRING,
            static function ($matches) use($skipImagesPattern) {
                $imgHtml = $matches[0] ?? '';

                if (preg_match($skipImagesPattern, stripslashes($imgHtml))) {
                    return $imgHtml;
                }

                return str_replace(
                    '<img ',
                    false === strpos($imgHtml, '=\\')
                        ? '<img loading="lazy" '
                        : '<img loading=\"lazy\" ',
                    $imgHtml
                );
            },
            $response->getContent()
        );

        $response->setContent($content);
    }

    /**
     * @return string
     */
    private function getImgHtmlClassesForRegex(): string
    {
        return implode('|', [
            'other123-class',
            'abracadabra-class',
            'gallery-placeholder__images',
        ]);
    }

    /**
     * @return string|null
     */
    private function getSkipImageByHtmlClassPattern(): ?string
    {
        $classes = $this->getImgHtmlClassesForRegex();

        if (empty($classes)) {
            return null;
        }

        return sprintf(
            self::IMG_HAS_HTML_CLASS_REGEX_FORMAT,
            $classes,
            $classes
        );
    }
}

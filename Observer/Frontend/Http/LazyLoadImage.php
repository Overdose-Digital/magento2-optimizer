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
use Overdose\MagentoOptimizer\Helper\Data;

/**
 * ResponseSendBeforeLazyLoadImage class
 *
 * Used to add loading=lazy to all images
 */
class LazyLoadImage extends AbstractObserver implements ObserverInterface
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
    protected function addLazyLoadToImage(Observer $observer): void
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

                if ($skipImagesPattern
                    && preg_match($skipImagesPattern, stripslashes($imgHtml))
                ) {
                    return $imgHtml;
                }

                $replace = false === strpos($imgHtml, '=\\')
                    ? '<img loading="lazy" '
                    : '<img loading=\"lazy\" ';

                return str_replace('<img', $replace, $imgHtml);
            },
            $response->getContent()
        );

        $response->setContent($content);
    }

    /**
     * @return string|null
     */
    protected function getSkipImageByHtmlClassPattern(): ?string
    {
        $classes = implode('|', $this->getLazyLoadExcludeImageHtmlClass());

        if (empty($classes)) {
            return null;
        }

        return sprintf(
            self::IMG_HAS_HTML_CLASS_REGEX_FORMAT,
            $classes,
            $classes
        );
    }

    /**
     * Check if loading=lazy attribute can be added to images
     *
     * @param RequestHttp $request
     * @return bool
     */
    public function isAddLazyLoadToImage(RequestHttp $request): bool
    {
        if (!$this->dataHelper->isLazyLoadImageEnabled()) {
            return false;
        }

        if ($request->isAjax()) {
            return false;
        }

        if (!$this->checkControllersIfExcluded(
            $request,
            Data::KEY_FIELD_EXCLUDE_CONTROLLERS,
            Data::KEY_SCOPE_LAZY_LOAD_IMAGE
        )) {
            return false;
        }

        if (!$this->checkPathIfExcluded(
            $request,
            Data::KEY_FIELD_EXCLUDE_PATH,
            Data::KEY_SCOPE_LAZY_LOAD_IMAGE
        )) {
            return false;
        }

        return true;
    }

    /**
     * Get classes list from config.
     *
     * @return string[]
     */
    protected function getLazyLoadExcludeImageHtmlClass(): array
    {
        try {
            $value = $this->serializer->unserialize(
                $this->dataHelper->getLazyLoadExcludeImageHtmlClassSerialized()
            );
        } catch (\Exception $e) {
            $value = null;
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $row) {
            if ($htmlClass = $row['html_class'] ?? null) {
                $result[] = $htmlClass;
            }
        }

        return array_unique(array_filter($result));
    }
}

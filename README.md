# Overdose Magento2 Optimizer

A module for improving web vital metrics.

## Installation
If NOT packagist, add repository first:
```
composer config repositories.overdose/module-magento-optimizer vcs git@github.com:Overdose-Digital/magento2-optimizer.git
```

For all cases
```
composer require overdose/module-magento-optimizer --no-update
composer update overdose/module-magento-optimizer
```

**‼️‼️ Ensure that default magento option `move_script_to_bottom` is disabled ‼️‼️**

## Functionality
### Main features
- Moves all js script in the bottom of page. Add html attribute `nodefer` to JS to skip moving.
- Loading JS scripts with a delay. Add html attribute `nolazy` to JS to skip moving.
- Remove Base Url from pages. Saves 55 bites on default Magento installation. Execution time about 0.000188 sec. So check profit if you decide enable it.
- Adds default html attribute `loading="lazy"` to all images. Add html attribute `nolazy` to image to skip loadind lazy.
- Features can be turned on separately, or work both at the same time.
- All features can be disabled for specific page by controller/action name or by URL path.

## Configurations:
- `od_optimizer/move_js_bottom_page/*`. JS options. Enabled by default.
- `od_optimizer/remove_base_url/*`. URL options. Disabled by default.
- `od_optimizer/lazy_load_image/*`. Lazy image options. Enabled by default except `gallery-placeholder__image`.
- `od_optimizer/js_load_delay/*`. Load JS with delay options. Disabled by default.

- For excluding controller: add in the field `{module}_{action}_{name}`, for example:`cms_index_index`
- For excluding paths: add in the field for example "/gear/bags.html"
- For excluding image from lazy loading use one of available methods: 
  - add "nolazy" attribute to img tag (this image will be skiped)
  - add appropriate HTML class of image via system config  
  `Configuration -> Magento Optimizer -> Use Lazy Loading Images -> Exclude Images by HTML Class` (images with this class will be skiped)

## Additional
![img.png](img.png)

## Support
Magento 2.2 | Magento 2.3 | Magento 2.4
:---: | :---: | :---:
? | ok | ok

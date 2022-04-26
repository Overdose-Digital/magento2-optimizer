# Overdose Magento2 Optimizer

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
- Optimizer moves all js script in the bottom of page, can be configured via adding controllers and paths (pages) which will not be affected 
- Remove Base Url from pages, can be configured via adding controllers and paths (pages) which will not be affected
- Add default html attribute `loading="lazy"` to all images.
- Features can be turned on separately, or work both at the same time.
- Fetures can be disabled for specific page by controller/action name ot by URL path.

## Configurations:
- `od_optimizer/move_js_bottom_page/*`. JS options. Enabled by default.
- `od_optimizer/remove_base_url/*`. URL options. Disabled by default.
- `od_optimizer/lazy_load_image/*`. Lazy image options. Enabled by default.

- For excluding controller: add in the field `{module}_{action}_{name}`, for example:`cms_index_index`
- For excluding paths: add in the field for example "/gear/bags.html"
- For excluding image from lazy loading: add "nolazy" attribute to img tag

## Additional
![img.png](img.png)

## Support
Magento 2.0 | Magento 2.1 | Magento 2.2 | Magento 2.3 | Magento 2.4
:---: | :---: | :---: | :---: | :---:
? | ? | ? | ok | ok

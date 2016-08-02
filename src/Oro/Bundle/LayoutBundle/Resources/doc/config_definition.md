# Config definition

## Overview

If you want to use different configuration for your **theme**, such as **assets**, **images** or **requirejs**, you need to put it to `layout/{theme_name}/config` folder.

## Assets

### Configuration
Assets configuration file should be placed in `layout/{theme_name}/config` folder and named `assets.yml`, for example `DemoBundle/Resources/views/layouts/first_theme/config/assets.yml`

**Example:**

```yaml
#DemoBundle/Resources/views/layouts/first_theme/config/assets.yml
styles:
    inputs:
        - 'bundles/demo/css/bootstrap.min.css'
        - 'bundles/demo/css/font-awesome.min.css'
    output: 'css/layout/first_theme/styles.css'
```

## Images

### Configuration
Images configuration file should be placed in `layout/{theme_name}/config` folder and named `images.yml`, for example `DemoBundle/Resources/views/layouts/first_theme/config/images.yml`

**Example:**

```yaml
#DemoBundle/Resources/views/layouts/first_theme/config/images.yml
types:
    main:
        label: orob2b.product.productimage.type.main.label
        dimensions: ~
        max_number: 1
    listing:
        label: orob2b.product.productimage.type.listing.label
        dimensions: ~
        max_number: 1
    additional:
        label: orob2b.product.productimage.type.additional.label
        dimensions: ~
        max_number: ~
```

## RequireJS definition

### Configuration
The configuration file should be placed in `layout/{theme_name}/config` folder and named `requirejs.yml`, for example `DemoBundle/Resources/views/layouts/base/config/requirejs.yml`

#### RequireJS configuration reference
LayoutBundle is depends on [RequireJSBundle](../../../RequireJSBundle/README.md),
that's why you can use configuration reference described in [Require.js config generation](../../../RequireJSBundle/README.md#requirejs-config-generation).

#### Additional configuration reference
| Option | Description | Required |
|------- |-------------|----------|
| `build_path` | Relative path from theme scripts folder (`web/js/layout/{theme_name}/`) | no |

**Example:**

```yaml
# src/Acme/Bundle/DemoBundle/Resources/views/layouts/base/config/requirejs.yml
config:
    build_path: 'scripts.min.js'
    shim:
        'jquery-ui':
            deps:
                - 'jquery'
    map:
        '*':
            'jquery': 'oroui/js/jquery-extend'
        'oroui/js/jquery-extend':
            'jquery': 'jquery'
    paths:
        'jquery': 'bundles/oroui/lib/jquery-1.10.2.js'
        'jquery-ui': 'bundles/oroui/lib/jquery-ui.min.js'
        'oroui/js/jquery-extend': 'bundles/oroui/js/jquery-extend.js'
```

When you execute a command in console:
```
php app/console oro:requirejs:build
```
Your result is `web/js/layout/base/scripts.min.js`

### RequireJS config provider
[RequireJSBundle](../../../RequireJSBundle/README.md) has its own config provider `oro_requirejs.provider.requirejs_config`
and **used in theme by default** (`web/js/oro.min.js` minimized scripts by default).
If you want use in theme your own minimized scripts you need to define block type `requires` with `provider_alias: { '@value': 'oro_layout_requirejs_config_provider' }`

**Example:**

```yaml
# src/Acme/Bundle/DemoBundle/Resources/views/layouts/base/layout.yml
...
requirejs_scripts:
    blockType: requires
    options:
        provider_alias: { '@value': 'oro_layout_requirejs_config_provider' }
...
```

`oro_layout_requirejs_config_provider` is alias of `oro_layout.provider.requirejs_config`

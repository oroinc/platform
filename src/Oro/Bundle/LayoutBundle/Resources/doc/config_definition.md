# Config definition

Table of content
----------------

* [Assets](#assets)
* [Images](#images)
* [RequireJS definition](#requirejs-definition)
* [Page Templates](#page-templates)

## Overview

If you want to use different configuration for your **theme**, such as **assets**, **images**, **requirejs** or **page_templates**, you need to put it to `layout/{theme_name}/config` folder.

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

```yml
#DemoBundle/Resources/views/layouts/first_theme/page/layout.yml
layout:
    actions:
    ...
    - '@add':
        id: styles
        parentId: head
        blockType: style
        options:
            src: '=data["asset"].getUrl(data["theme"].getStylesOutput(context["theme"]))'
    ...
```

**Example how to create 2 or more outputs:**

```yaml
#DemoBundle/Resources/views/layouts/first_theme/config/assets.yml
libraries:
    inputs:
        - 'bundles/demo/css/bootstrap.min.css'
        - 'bundles/demo/css/font-awesome.min.css'
    output: 'css/layout/first_theme/lib.css'

own_styles:
    inputs:
        - 'bundles/demo/css/custom.min.css'
        - 'bundles/demo/css/additional.min.css'
    output: 'css/layout/first_theme/styles.css'
```

```yml
#DemoBundle/Resources/views/layouts/first_theme/page/layout.yml
layout:
    actions:
    ...
    - '@add':
        id: libraries
        parentId: head
        blockType: style
        options:
            src: '=data["asset"].getUrl(data["theme"].getStylesOutput(context["theme"], "libraries"))'
    - '@add':
        id: own_styles
        parentId: head
        blockType: style
        options:
            src: '=data["asset"].getUrl(data["theme"].getStylesOutput(context["theme"], "own_styles"))'
    ...
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

## Page Templates
A **page_template** is a collection of files that expand the visual presentation for one or more route names.

### Configuration
Page templates configuration file should be placed in `layout/{theme_name}/config` folder and named `page_templates.yml`, 
for example `DemoBundle/Resources/views/layouts/first_theme/config/page_templates.yml`.
All page template **layout updates** stored in `layout/{theme_name}/{route_name}/page_template/{page_template_KEY}/` folder, 
for example `DemoBundle/Resources/views/layouts/first_theme/demo_first_route_name/page_template/custom/layout.yml`.

#### Additional configuration reference
| Option | Description | Required |
|------- |-------------|----------|
| `label` | Label will be displayed in the page template management UI. | yes |
| `route_name` | Route name identifier, used in the path where **layout updates** stored. | yes |
| `key` | Key used in the path where **layout updates** stored. | yes |
| `description` | Description will be displayed in the page template management UI. | no |
| `screenshot` | Screenshot for preview. This will be displayed in the page template management UI. | no |
| `enabled` | Enable/Disable page template | no |

**Example:**

```yaml
#DemoBundle/Resources/views/layouts/first_theme/config/page_templates.yml
templates:
    -
        label: Custom page template
        description: Custom page template description
        route_name: demo_first_route_name
        key: custom
    -
        label: Additional page template
        description: Additional page template description
        route_name: demo_first_route_name
        key: additional
    -
        label: Additional page template
        description: Additional page template description
        route_name: demo_second_route_name
        key: additional
titles:
    demo_first_route_name: First route name title
    demo_second_route_name: Second route name title
```

_NOTICE:_ Pay attention that page templates inherit parent themes. 
You can simple override existing page template, just add **layout update** file to page template path in your child theme, 
for example if `first_theme` is parent theme of `second_theme` you need to put page template to 
`DemoBundle/Resources/views/layouts/second_theme/demo_first_route_name/page_template/custom/layout.yml`.

Also you can disable some page templates adding `enabled: false`.

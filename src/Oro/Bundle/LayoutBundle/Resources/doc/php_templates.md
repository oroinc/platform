# Using PHP instead of Twig for templates

Symfony defaults to Twig for its template engine, but you can still use plain PHP templates if necessary. Symfony provides equally good support of both templating engines.

If you preference is PHP templates, this article describes how to enable and use PHP templates with OroLayout bundle in the applications built on OroPlatform.

The Symfony framework documentation contains additional useful information about PHP templates and form rendering customization: 
* [How to Use PHP instead of Twig for Templates](http://symfony.com/doc/current/templating/PHP.html)
* [How to Customize Form Rendering](http://symfony.com/doc/current/form/form_customization.html)

## Configure OroLayoutBundle

Only one templating engine can be used at a time in an OroPlatform application. By default, OroLayoutBundle is configured to use Twig. If you decide to use PHP templates, you should disable Twig and make PHP templating the default templating engine in the application configuration file:

```Yaml
oro_layout:
    templating:
        default: php
        twig:
            enabled: false
```

## Modify layouts to use PHP templates

The default ["base"](https://github.com/orocrm/platform/blob/84b1d81ac3a7198bdd0eed3dd76db48a72c10cd3/src/Oro/Bundle/UIBundle/Resources/views/layouts/base/page/layout.yml#L3-L4]) OroPlatform theme uses Twig templates. You should use a different approach in your default.yml file in your theme's folder: 

```Yaml
#MyBundle/Resources/views/layouts/first_theme/default.yml
layout:
    actions:
        - @setBlockTheme:
            themes: 'MyBundle:layouts/first_theme/php'
        - @addTree:
            items:
                head:
                    blockType: head
                meta:
                    blockType: meta
                    options:
                        http_equiv: Content-Type
                        content: "text/html; charset=utf-8"
                body:
                    blockType: body
                content:
                    blockType: container
                    options:
                        attr:
                            class: content
                greeting:
                    blockType: block
            tree:
                root:
                    head:
                        meta: ~
                    body:
                        content:
                            greeting: ~
```

The example above creates a standard web page structure (head, metadata, and body) with two custom blocks in the body (content and greeting). And in this layout we specified a different "block theme" (so that the templating engine will know where to find our PHP templates):

```Yaml
    actions:
        - @setBlockTheme:
            themes: 'MyBundle:layouts/first_theme/php'
```

## Creating templates

As you are not using Twig anymore, you should provide the PHP templates for the blocks used in the layout.

The PHP templates can be very simple, like in the following example of the `greeting` block template where we just want to display "Hello!":

```php
#MyBundle/Resources/views/layouts/first_theme/php/_greeting_widget.html.php
<p>Hello!</p>
```

You can also create more complex templates that use variables and functions provided by the layout. This is an example of the `content` block template:

```php
#MyBundle/Resources/views/layouts/first_theme/php/_content_widget.html.php
<div <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    <h1>Welcome back</h1>
    <?php echo $view['layout']->widget($block); ?>
</div>
```

The layout and templates from our examples will produce the following HTML output:

```html
<!DOCTYPE html>
<html>
    <head class="foo">
        <meta http_equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <body>
        <div class="content">
            <h1>Welcome back</h1>
            <p>Hello!</p>
        </div>
    </body>
</html>
```

A number of fully working PHP templates for various block types is already included in OroLayoutBundle - check the [`src/Oro/Bundle/LayoutBundle/Resources/views/Layout/php`](https://github.com/orocrm/platform/tree/master/src/Oro/Bundle/LayoutBundle/Resources/views/Layout/php) folder to see all the examples.

We prefer to use Twig in our products (e.g. see the [default theme](https://github.com/orocommerce/orocommerce/tree/master/src/OroB2B/Bundle/FrontendBundle/Resources/views/layouts/default) in [OroCommerce](https://www.orocommerce.com/)) to better express presentation and to avoid including the program logic in the templates. Your choice may be different based on the needs of your customers and the approach you selected to build your OroPlatform-based application.

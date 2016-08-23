
# Using PHP Templating instead of Twig

This article describes using php templates in layouts.
In the official Symfony documentation you can find the following useful information: 
* [How to Use PHP instead of Twig for Templates](http://symfony.com/doc/current/templating/PHP.html).
* [How to Customize Form Rendering](http://symfony.com/doc/current/form/form_customization.html).

## Configuring LayoutBundle

Only one template engine can be used for the entire <ORO deployment>. If you decide to use php templates, disable the Twig and set php as default template engine in the `config.yml`:

```Yaml
oro_layout:
    templating:
        default: php
        twig:
            enabled: false
```

## Defining Layouts with php templates

To modify the default layout, update the default.yml file located in the theme's folder. 
For example, the file below defines the standard page structure (head, metadata, and body) and two custom blocks in the body (content and greeting):

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

To help php search for the necessary template, we specified location of the theme's php folder:

```Yaml
    actions:
        - @setBlockTheme:
            themes: 'MyBundle:layouts/first_theme/php'
```


## Overriding Templates

You can override contents of any block template. For example, a `greeting` block that displays 'Hello!' <how is it linked to the filename? _..._widget.html.php?>:

```php
#MyBundle/Resources/views/layouts/first_theme/php/_greeting_widget.html.php
<p>Hello!</p>
```

This simple template displays "Hello!" text. So let's create more complex template to override template for `content` block:

```php
#MyBundle/Resources/views/layouts/first_theme/php/_content_widget.html.php
<div <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    <h1>Welcome back</h1>
    <?php echo $view['layout']->widget($block); ?>
</div>
```

Html output will be the following:

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

To get a deeper understanding of using php templating with LayoutBundle take a look at folder with default templates: `platform/src/Oro/Bundle/LayoutBundle/Resources/views/Layout/php`.

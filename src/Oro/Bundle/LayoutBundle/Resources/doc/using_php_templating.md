Using PHP Templating instead of Twig
====================================

This article describes how to use php templates in layouts.
You can find some useful information on using php templates in Symfony's documentation: [How to Use PHP instead of Twig for Templates](http://symfony.com/doc/current/templating/PHP.html).
For further reading see Form component's documentation: [How to Customize Form Rendering](http://symfony.com/doc/current/form/form_customization.html).

Configuring LayoutBundle
------------------------

In current implementation you can't use multiple templating engines at once. So you should disable Twig templating and set php templating as default in your `config.yml`:

```Yaml
oro_layout:
    templating:
        default: php
        twig:
            enabled: false
```

Defining Layouts
----------------

Let's create default layout update file in for our theme's folder:

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

Here we have skeleton for our pages (html, head, ...). Imortant thing is that we specified theme for our blocks (`MyBundle:layouts/first_theme/php`), this is how php templating knows where to search for templates.

Overriding Templates
--------------------

Now we can override any of our block templates. For example let's define template for `greeting` block:

```php
#MyBundle/Resources/views/layouts/first_theme/php/_greeting_widget.html.php
<p>Hello!</p>
```

This is template pretty simple, it just displays text "Hello!". So let's create more complex template to override template for `content` block:

```php
#MyBundle/Resources/views/layouts/first_theme/php/_content_widget.html.php
<div <?php echo $view['layout']->block($block, 'block_attributes') ?>>
    <h1>Greeting</h1>
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
            <h1>Greeting</h1>
            <p>Hello!</p>
        </div>
    </body>
</html>
```

To get a deeper understanding of using php templating with LayoutBundle take a look at folder with default templates: `platform/src/Oro/Bundle/LayoutBundle/Resources/views/Layout/php`.

Quick start
===========

The following examples may help to start using layouts in your application.

Create the layout theme
-----------------------

The theme definition should be placed at theme folder and named `theme.yml`, for example `DemoBundle/Resources/views/layouts/first_theme/theme.yml`
Deprecated method: placed  `Resources/config/oro/` and named `layout.yml`, for example `DemoBundle/Resources/config/oro/layout.yml`:

```yaml
#DemoBundle/Resources/views/layouts/first_theme/theme.yml
label:  Test Theme
icon:   bundles/demo/images/favicon.ico
groups: [ main ]

#DemoBundle/Resources/config/oro/layout.yml
oro_layout:
    themes:
        first_theme:
            label:  Test Theme
            icon:   bundles/demo/images/favicon.ico
            groups: [ main ]
```
See [theme definition](./theme_definition.md) topic for more details.

Set default theme
-----------------

Now you need to setup your layout theme as the default one for your application. To do this you need to add the following configuration to the `app/config/config.yml` file:

```yaml
oro_layout:
    active_theme: first_theme
```

Create layout update files
--------------------------

To create a skeleton of your layout theme create a layout update file and place it in `Resources/views/layouts` directory, for example `Resources/views/layouts/first_theme/default.yml`:

```yaml
layout:
    actions:
        - @setBlockTheme:
            themes: 'DemoBundle:layouts:first_theme/default.html.twig'
        - @addTree:
            items:
                head:
                    blockType: head
                theme_icon:
                    blockType: external_resource
                    options:
                        href: '=data["theme"].getIcon()'
                        rel: shortcut icon
                head_style:
                    blockType: container
                head_script:
                    blockType: container
                body:
                    blockType: body
                header:
                    blockType: container
                navigation_bar:
                    blockType: container
                main_menu:
                    blockType: container
                    options:
                        attr:
                            id: main-menu
                left_panel:
                    blockType: container
                    options:
                        attr:
                            id: left-panel
                main_panel:
                    blockType: container
                    options:
                        attr:
                            id: main-panel
                            class: content
                content:
                    blockType: container
                footer:
                    blockType: container
            tree:
                root:
                    head:
                        theme_icon: ~
                        head_style: ~
                        head_script: ~
                    body:
                        header:
                            navigation_bar: ~
                            main_menu: ~
                        left_panel: ~
                        main_panel:
                            content: ~
                        footer: ~
```

See [layout update](./layout_update.md) topic for more details.

Customize rendering of your theme
---------------------------------

As you have seen in the previous section we have added `setBlockTheme` action there. The block theme responsible for defining how layout blocks are rendered. Let's create a simple block theme and place it in `Resources/views/layouts/first_theme/default.html.twig`:

```twig
{% block head_widget %}
    <head{{ block('block_attributes') }}>
        <meta http-equiv="cache-control" content="max-age=0" />
        <meta http-equiv="cache-control" content="no-cache" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="pragma" content="no-cache" />
        {{ block_widget(block) }}
    </head>
{% endblock %}

{% block _header_widget %}
    <header{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </header>
{% endblock %}

{% block _main_menu_widget %}
    <div{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _left_panel_widget %}
    <div{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _footer_widget %}
    <footer{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </footer>
{% endblock %}

{% block _main_panel_widget %}
    <div{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </div>
{% endblock %}
```

The rendering of the layouts is very similar to Symfony Forms, but `block_` prefix is used instead of `form_` prefix. Read appropriate topics in Symfony documentation, for example [How to Customize Form Rendering](http://symfony.com/doc/current/cookbook/form/form_customization.html).

Create a controller
-------------------

Now the layout is ready to work. To test it let's create a test controller:

```php
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Oro\Bundle\LayoutBundle\Annotation\Layout;

class UserController extends Controller
{
    /**
     * @Route("/test", name="demo_layout_test")
     * @Layout
     */
    public function testAction()
    {
        return [];
    }
}
```

When you open this page in a browser you can see the HTML like this:

```html
<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <meta http-equiv="cache-control" content="max-age=0" />
        <meta http-equiv="cache-control" content="no-cache" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="pragma" content="no-cache" />
        <link rel="shortcut icon" href="bundles/demo/images/favicon.ico"/>
    </head>
    <body>
        <header>
            <div id="main-menu">
            </div>
        </header>
        <div id="left-panel">
        </div>
        <div id="main-panel" class="content">
        </div>
        <footer>
        </footer>
    </body>
</html>
```

Now let's make three simple changes to the layout of this page:

 - Set the title
 - Add some text in the content area
 - Add additional CSS class to the main panel

To do this just create the layout update file and place it in the `Resources/views/layouts/first_theme/demo_layout_test` directory, for example `DemoBundle/Resources/views/layouts/first_theme/demo_layout_test/test.yml`:

```yaml
layout:
    actions:
        - @setOption:
            id: head
            optionName: title
            optionValue: Hello World!
        - @add:
            id: test_text
            parentId: content
            blockType: text
            options:
                text: Layouts. Hello World!
        - @appendOption:
            id: main_panel
            optionName: attr.class
            optionValue: test-css-class
```

Please note that the file is placed in the route specific folder and as the result it will be executed only if you go to `demo_layout_test` route.

Now when you open the test page in a browser you can see the HTML like this:

```html
<!DOCTYPE html>
<html>
    <head>
        <title>Hello World!</title>
        <meta http-equiv="cache-control" content="max-age=0" />
        <meta http-equiv="cache-control" content="no-cache" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="pragma" content="no-cache" />
        <link rel="shortcut icon" href="bundles/demo/images/favicon.ico"/>
    </head>
    <body>
        <header>
            <div id="main-menu">
            </div>
        </header>
        <div id="left-panel">
        </div>
        <div id="main-panel" class="content test-css-class">
            Layouts. Hello World!
        </div>
        <footer>
        </footer>
    </body>
</html>
```

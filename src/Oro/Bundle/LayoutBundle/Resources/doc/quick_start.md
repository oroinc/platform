# Quick Start

The following examples can be helpful if you wish to use layouts in your application.

## Create Layout Theme

The theme definition should be placed in theme folder and named `theme.yml`, for example `DemoBundle/Resources/views/layouts/first_theme/theme.yml`

```yaml
#DemoBundle/Resources/views/layouts/first_theme/theme.yml
label:  Test Theme
icon:   bundles/demo/images/favicon.ico
groups: [ main ]
```
See the [theme definition](./theme_definition.md) topic for more details.

## Use Layout Theme Configuration

Configuration files such as **assets**, **images** or **requirejs** should be placed in the `layout/{theme_name}/config` folder.

See the [config definition](./config_definition.md) topic for more details.

## Set Default Theme

To set a default layout theme for your application, add the following configuration to the `config/config.yml` file:

```yaml
oro_layout:
    active_theme: first_theme
```

## Create Layout Update Files

To build a frame of your layout theme, create a layout update file and place it in the `Resources/views/layouts` directory, for example `Resources/views/layouts/first_theme/default.yml`:

```yaml
layout:
    actions:
        - '@setBlockTheme':
            themes: 'DemoBundle:layouts:first_theme/default.html.twig'
        - '@addTree':
            items:
                head:
                    blockType: head
                theme_icon:
                    blockType: external_resource
                    options:
                        href: '=data["theme"].getIcon("first_theme")'
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

See the [layout update](./layout_update.md) topic for more details.

## Customize Rendering of Your Theme

In the previous section, we added the `setBlockTheme` action. The block theme is responsible for defining the way layout blocks are rendered. 

As an example, we are going to create a simple block theme and place it in `Resources/views/layouts/first_theme/default.html.twig`:

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

The rendering of the layouts is very similar to Symfony Forms, but the `block_` prefix is used instead of the `form_` prefix. You can find more information on customizing form rendering in the relevant Symfony documentation:[How to Customize Form Rendering](http://symfony.com/doc/current/cookbook/form/form_customization.html).

## Create a Controller

The layout is now ready. To test, we create a test controller:

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

The following HTML output is produced: 


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

We now make three simple changes to the layout of this page:

 - Set the title
 - Add some text in the content area
 - Add additional CSS class to the main panel

To do this, we create the layout update file and place it in the `Resources/views/layouts/first_theme/demo_layout_test` directory, for example `DemoBundle/Resources/views/layouts/first_theme/demo_layout_test/test.yml`:

```yaml
layout:
    actions:
        - '@setOption':
            id: head
            optionName: title
            optionValue: Hello World!
        - '@add':
            id: test_text
            parentId: page_content
            blockType: text
            options:
                text: Layouts. Hello World!
        - '@appendOption':
            id: main_panel
            optionName: attr.class
            optionValue: test-css-class
```

As the file is placed in the route specific folder, the update will apply only to the page related to the `demo_layout_test` route.

When you open the test page in a browser, the following HTML output is produced:

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

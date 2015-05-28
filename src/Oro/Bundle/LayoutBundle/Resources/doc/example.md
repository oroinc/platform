Layout example implementation
=============================

The goal of this guide is to demonstrate the capabilities of the layout engine and show how different layout blocks can be used to implement a simple page.
This guide is intended for those already familiar with layouts. So, please, read our [Quick Start guide](./quick_start.md) before proceeding.
Let's consider an example of layout implementation. Imagine you need to create a product page that looks like this:

![Product page example](./images/product_page.png "Product page example")

Getting started
-----------------------

As described in the [Quick Start guide](./quick_start.md), you can create a new theme for this page or use the default one.
Let's assume that we already have a new theme created and activated. And the controller is set up.
Similarly to the Quick Start guide, we create the skeleton of the theme to hold the design of common page elements - head, body, header, footer, etc.
create a layout update file and place it in `Resources/views/layouts` directory, for example `Resources/views/layouts/first_theme/default.yml`:

```yaml
layout:
    actions:
        - @setBlockTheme:
            themes: 'OroLayoutBundle:layouts:first_theme/default.html.twig'
        - @addTree:
            items:
                head:
                    blockType: head
                meta:
                    blockType: meta
                    options:
                        http_equiv: Content-Type
                        content: "text/html; charset=utf-8"
                theme_icon:
                    blockType: external_resource
                    options:
                        href: { @value: $data.theme.icon }
                        rel: shortcut icon
                head_style:
                    blockType: container
                head_script:
                    blockType: container
                body:
                    blockType: body
                page_container:
                    blockType: container
                    options:
                        attr:
                            class: page
                header:
                    blockType: container
                navigation:
                    blockType: container
                    options:
                        attr:
                            id: header-nav
                search:
                    blockType: container
                    options:
                        attr:
                            id: header-search
                main_container:
                    blockType: container
                    options:
                        attr:
                            class: 'main-container col2-left-layout'
                left_panel:
                    blockType: container
                    options:
                        attr:
                            id: col-left
                main_panel:
                    blockType: container
                    options:
                        attr:
                            class: col-main
                content:
                    blockType: container
                footer:
                    blockType: container
            tree:
                root:
                    head:
                        meta: ~
                        theme_icon: ~
                        head_style: ~
                        head_script: ~
                    body:
                        page_container:
                            header:
                                navigation: ~
                                search: ~
                            main_container:
                                left_panel: ~
                                main_panel:
                                    content: ~
                            footer: ~
```

See [layout update](./layout_update.md) topic for more details.

Customize rendering of blocks
---------------------------------

As you have seen in the previous section we have added `setBlockTheme` action there. The block theme responsible for defining how layout blocks are rendered.
Let's define these blocks in `Resources/views/layouts/first_theme/default.html.twig`:

```twig
{% block _page_container_widget %}
    <div{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _header_widget %}
    <header id="header" class="page-header">
        {{ block_widget(block) }}
    </header>
{% endblock %}

{% block _navigation_widget %}
    <div{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _search_widget %}
    <div{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _main_container_widget %}
    <div{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _left_panel_widget %}
    <div{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _main_panel_widget %}
    <div{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _footer_widget %}
    <footer{{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </footer>
{% endblock %}
```

When you open this page in a browser you can see the HTML like this:

```html
<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
        <link rel="shortcut icon" href="bundles/demo/images/favicon.ico"/>
    </head>
    <body>
        <div class="page">
            <header id="header" class="page-header">
                <div id="header-nav"></div>
                <div id="header-search"></div>
            </header>
            <div class="main-container col2-left-layout">
                <div id="col-left"></div>
                <div class="col-main"></div>
            </div>
            <footer></footer>
        </div>
    </body>
</html>
```

In our example we need to add `lang="en"` to the `<html>` tag. For this we will redefine the `root_widget` block in our default.html.twig.
```twig
{% block root_widget %}
    <!DOCTYPE {{ doctype|default('html') }}>
    <html{{ block('block_attributes') }}>
    {{ block_widget(block) }}
    </html>
{% endblock %}
```
Now we can set the `lang` attribute in our layout update file using `setOption` action
 ```yaml
     - @setOption:
         id: root
         optionName: attr.lang
         optionValue: en
 ```

Now we need to make customization for our product page. For this we'll create the layout update file and place it in the `Resources/views/layouts/first_theme/demo_layout_test` directory, for example `DemoBundle/Resources/views/layouts/first_theme/demo_layout_test/test.yml`:
Please note that the file is placed in the route specific folder and as the result it will be executed only if you go to `demo_layout_test` route.

Adding CSS and JS
--------------------

Let's add CSS and JS required for our product page. For this we'll use `style` and `script` block types:

```yaml
layout:
    actions:
        - @add:
            id: style_calendar
            parentId: head_style
            blockType: style
            options:
                src: 'js/calendar/calendar.css'
        - @add:
            id: script_prototype
            parentId: head_script
            blockType: script
            options:
                src: 'js/prototype/prototype.js'
        - @add:
            id: script_cookie_path
            parentId: head_script
            blockType: script
            options:
                content: "Mage.Cookies.path = '/';"
```

As you can see we can add inline CSS or JS using `content` option or use separate files specified in `src` option.
Imagine you need to add some scripts for IE only using html comments. In this case we can't use `script` block type but we can use default block type with customized template.
```yaml
layout:
    actions:
        - @setBlockTheme:
            themes: 'OroLayoutBundle:layouts:first_theme/demo_layout_test/test.html.twig'
        - @add:
            id: script_ie
            parentId: head_script
            blockType: block
```

As in case with default layout we define a new block theme for our layout update. Let's create `Resources/views/layouts/first_theme/demo_layout_test/test.html.twig` file:
```twig
{% block _script_ie_widget %}
    <!--[if lt IE 7]>
        <script type="text/javascript">
            //<![CDATA[
                var BLANK_URL = '/js/blank.html';
            //]]>
        </script>
    <![endif]-->
{% endblock %}
```

Changing Layout blocks positioning
-----------------------------------

In our default theme we have a two-column layout. But for our example page we'll need just one column. Let's remove the `left_panel` block and change the class for `main_container`:
```yaml
layout:
    actions:
        - @remove:
            id: left_panel
        - @replaceOption:
            id: main_container
            optionName: attr.class
            oldOptionValue: col2-left-layout
            newOptionValue: col1-layout
```

Here we know the option value that we need to replace. But in case if you just want to add another option to already existing ones you can use `appendOption` action:
```yaml
layout:
    actions:
        - @appendOption:
            id: body
            optionName: attr.class
            optionValue: catalog-product-view
```

Also for our example, we'll need to add a wrapper for the body content. For this we'll add a new `container` to the body and move the content into it.
```yaml
layout:
    actions:
        - @add:
            id: body_wrapper
            blockType: container
            parentId: body
        - @move:
            id: page_container
            parentId: body_wrapper
```
Since `container` block type does not render any html, we'll add a template specifically for our new wrapper:
```twig
{% block _body_wrapper_widget %}
    <div class="wrapper">
        {{ block_widget(block) }}
    </div>
{% endblock %}
```

Let's check what is rendered in the browser. You should be getting something like this:

```html
<!DOCTYPE html>
<html lang="en">
    <head>
        <title></title>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
        <link rel="shortcut icon" href="bundles/demo/images/favicon.ico"/>
        <link rel="stylesheet" type="text/css" href="js/calendar/calendar.css">
        <!--[if lt IE 7]>
            <script type="text/javascript">
                //<![CDATA[
                    var BLANK_URL = '/js/blank.html';
                //]]>
            </script>
        <![endif]-->
        <script type="text/javascript">
            Mage.Cookies.path = '/';
        </script>
        <script type="text/javascript" src="js/prototype/prototype.js"></script>
    </head>
    <body class="catalog-product-view">
        <div class="wrapper">
            <div class="page">
                <header id="header" class="page-header">
                    <div id="header-nav"></div>
                    <div id="header-search"></div>
                </header>
                <div class="main-container col1-layout">
                    <div class="col-main"></div>
                </div>
                <footer></footer>
            </div>
        </div>
    </body>
</html>
```

Using page specific data
-----------------------------------

On our test page we have a "This is a demo store." notice. It is clear that this block should be visible only on certain conditions.
For simplicity, we will check if the application is running in debug mode by checking the `debug` value in the Layout context which is added by [ApplicationContextConfigurator](../../Layout/Extension/ApplicationContextConfigurator.php).
Let's add block template in our `test.html.twig`:
```twig
{% block _demo_notice_widget %}
    <div class="global-site-notice demo-notice">
        <div class="notice-inner"><p>{{ "This is a demo store. Any orders placed through this store will not be honored or fulfilled."|trans }}</p></div>
    </div>
{% endblock %}
```
Now we can added it to the layout depending on the condition using the `visible` option:
```yaml
layout:
    actions:
        - @add:
            id: demo_notice
            parentId: body
            blockType: block
            options:
                visible: { @value: $context.debug }
```
Note that if `visible` evaluates to false, the block will not be added to the final layout at all.

Every product page is different since it contains product related data. The layout engine allows you to operate this data in the layout update files.
Please, make sure you are familiar with [layout context](layout_context.md) and [layout data](layout_data.md) topics.

Since product data is page specific (used on product page only), we'll be adding it to `data` collection of the layout context using a [context configurator](layout_context.md#context-configurators).

```php
namespace Acme\Bundle\ProductBundle\Layout\Extension;;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

class ProductContextConfigurator implements ContextConfiguratorInterface
{
    /** @var Request|null */
    protected $request;

    /**
     * Synchronized DI method call, sets current request for further usage
     *
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->data()->setDefault(
            'product',
            '$request.product_id',
            function () {
                if (!$this->request) {
                    throw new \BadMethodCallException('The request expected.');
                }
                $productId = $this->request->query->get('product_id') ?: $this->request->request->get('product_id');

                return $this->getProductData($productId);
            }
        );
    }

    /*
     * Demo function. Data should be selected from the database instead.
     *
     * @param int $productId
     * @return array
     */
    protected function getProductData($productId)
    {
        $productData = [
            '99' => [
                'id'          => 99,
                'name'        => 'Chelsea Tee',
                'description' => 'Ultrasoft, lightweight V-neck tee. 100% cotton. Machine wash.',
                'category'    => 'Men',
                'subcategory' => 'Tees, Knits and Polos',
                'url'         => '/chelsea-tea.html'
            ]
        ];

        return isset($productData[$productId]) ? $productData[$productId] : [];
    }
}
```

The product Id is received from the request, and based on it we obtain the rest of product data. It can be fetched from database or other sources, but for simplicity we use a simple array here.
To enable it we have to register it in the DI container with the `layout.context_configurator` tag:
```yaml
    acme_product.layout.context_configurator.product:
        class: Acme\Bundle\ProductBundle\Layout\Extension\ProductContextConfigurator
        calls:
            - [setRequest, [@?request=]]
        tags:
            - { name: layout.context_configurator }
```

Now we can use product data in the layout update to add page title, meta description and canonical url:
```yaml
layout:
    actions:
        - @setOption:
            id: head
            optionName: title
            optionValue:
                @join:
                    - ' - '
                    - { @value: {@value: $data.product.name} }
                    - { @value: {@value: $data.product.subcategory} }
                    - { @value: {@value: $data.product.category} }
        - @add:
            id: meta_description
            parentId: head
            blockType: meta
            options:
                name: 'description'
                content: {@value: $data.product.description}
        - @add:
            id: link_canonical
            parentId: head
            blockType: external_resource
            options:
                rel:  canonical
                href: {@value: $data.product.url}
```
Note how we use [Join](../../../../Component/ConfigExpression/Func/Join.php) function to compose the page title from different product fields.


Let's consider a another example of using data providers.
To implement a language switcher we'll create a separate data provider class, since this data is used throughout all pages.

```php
namespace Acme\Bundle\LocaleBundle\Layout\Extension;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

class ProductDataProvider implements DataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
	    return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return [
            'default_language'    => 'english',
            'available_languages' => [
                'english' => 'English',
                'french'  => 'French'
            ]
        ];
    }
}
```

We need to register our data provider in the DI container:
```yaml
    acme_locale.layout.data_provider.languages:
        class: Acme\Bundle\LocaleBundle\Layout\Extension\LanguagesDataProvider
        tags:
            - { name: layout.data_provider, alias: languages }
```

Now we can refer to language data the same way as to product data. In our layout update file we can add:

```yaml
layout:
    actions:
        - @add:
            id: lang_switch
            parentId: page_container
            blockType: block
            options:
               vars:
                  default_language: { @value: $data.languages.default_language }
                  available_languages: { @value: $data.languages.available_languages }
                  product_url: { @value: $data.product.url }
```

And create the block template for the language switcher:
```twig
{% block _lang_switch_widget %}
    <div class="header-language-background">
        <div class="header-language-container">
            <div class="store-language-container">
                <div class="form-language">
                    <label for="select-language">Your Language:</label>
                    <select id="select-language" title="Your Language" onchange="window.location.href=this.value">
                        {% for code, label in available_languages %}
                            <option value="{{ product_url }}?___store={{ code }}">{{ label }}</option>
                        {% endfor %}
                    </select>
                </div>
            </div>
            <p class="welcome-msg">Welcome </p>
        </div>
    </div>
{% endblock %}
```

This will render the language switcher in the browser but will not know which language has been selected. For this we need to add a context configurator which will store the selected language.
Similar to `ProductContextConfigurator` we'll fetch the language code from the request and save it in the layout context.

```php
<?php
namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

class LocaleContextConfigurator implements ContextConfiguratorInterface
{
    /** @var Request|null */
    protected $request;

    /**
     * Synchronized DI method call, sets current request for further usage
     *
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->data()->setDefault(
            'current_language',
            '$request.___store',
            function () {
                if (!$this->request) {
                    throw new \BadMethodCallException('The request expected.');
                }
                $locale = $this->request->query->get('___store') ?: $this->request->request->get('___store');

                return $locale;
            }
        );
    }
}
```

Register locale context configurator:
```yaml
    acme_product.layout.context_configurator.locale:
        class: Acme\Bundle\ProductBundle\Layout\Extension\LocaleContextConfigurator
        calls:
            - [setRequest, [@?request=]]
        tags:
            - { name: layout.context_configurator }
```
We also need to modify our block template to have the language dropdown preselect the current value:
```twig
    <select id="select-language" title="Your Language" onchange="window.location.href=this.value">
        {% for code, label in available_languages %}
            <option value="{{ product_url }}?___store={{ code }}" {% if code == lang %}selected="selected"{% endif %}>{{ label }}</option>
        {% endfor %}
    </select>
```

Now if you go to `/layout/test?product_id=99&___store=french` url you'll see that French language is preselected.

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
namespace Acme\Bundle\LocaleBundle\Layout\Extension;

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
        class: Acme\Bundle\LocaleBundle\Layout\Extension\LocaleContextConfigurator
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


Working with forms
-----------------------------------

Let's implement a simple search form by means of the layout engine.
To use the form in layouts we need to configure the layout context first. Since the search form persists on many pages we will add it to the layout context using another context configurator:
```php
namespace Acme\Bundle\SearchBundle\Layout\Extension;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

class SearchContextConfigurator implements ContextConfiguratorInterface
{
    /*
     * FormFactory
     */
    protected $formFactory;

    public function __construct(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setDefaults(
                [
                    'search_form' => function (Options $options, $value) {
                        if (null === $value) {
                            $value = $this->createSearchForm();
                        }

                        return $value;
                    }
                ]
            )
            ->setAllowedTypes(['search_form' => ['null', 'Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface']]);
    }

    /*
     * @return FormAccessor
     */
    protected function createSearchForm()
    {
        $form = $this->formFactory->create('form');
        $form->add(
            'search',
            'search',
            [
                'attr' => [
                    'maxlength'   => 128,
                    'placeholder' => 'Search entire store here...',
                ]
            ]
        );

        return new FormAccessor($form);
    }
}
```

Registering in the DI container:

```yaml
    acme_search.layout.context_configurator.search:
        class: Acme\Bundle\SearchBundle\Layout\Extension\SearchContextConfigurator
        arguments:
            - @form.factory
        tags:
            - { name: layout.context_configurator }
```

Now we can add our search from into the layout:

```yaml
layout:
    actions:
        - @setBlockTheme:
            themes: 'OroLayoutBundle:layouts:first_theme/demo_layout_test/search.html.twig'
        - @addTree:
            items:
                'searh_form:start':
                    blockType: form_start
                    options:
                        form_name: search_form
                        attr:
                            id: search_mini_form
                search_field:
                    blockType: form_field
                    options:
                        form_name: search_form
                        field_path: search
                search_button:
                    blockType: button
                    options:
                        action: submit
                        text: Submit
                        attr:
                            class: button search-button
                            title: Search
                search_autocomplete:
                    blockType: block
                'searh_form:end':
                    blockType: form_end
                    options:
                        form_name: search_form
            tree:
                search:
                    'searh_form:start': ~
                    search_field: ~
                    search_button: ~
                    search_autocomplete: ~
                    'searh_form:end': ~
```

In `search.html.twig` will define the search autocomplete block:
```twig
{% block _search_autocomplete_widget -%}
    <div id="search_autocomplete" class="search-autocomplete"></div>
    <script type="text/javascript">
        var searchForm = new Varien.searchForm('search_mini_form', 'search', '');
        searchForm.initAutocomplete('/catalogsearch/ajax/suggest/', 'search_autocomplete');
    </script>
{%- endblock %}
```

Note that we are using separate block types `form_start`, `form_end` and `form_field` to render the form. This allows us to easily add content inside the form (e.g. autocomplete block)
For all this block fields we need to specify `form_name` option to bind it to our custom `search_form` form.

As the result you'll be getting and HTML like this:
```html
<div id="header-search">
    <form id="search_mini_form" action="/catalogsearch/result/" method="get">
        <div class="control-group">
            <label class="control-label required" for="form_search-uid-556af114b1fb4">Search<em>*</em></label>
            <div class="controls">
                <input type="search" id="form_search-uid-556af2fc646e0" name="form[search]" required="required" maxlength="128" placeholder="Search entire store here..." data-ftid="form_search">
            </div>
        </div>
        <button class="button search-button" title="Search" type="submit">Submit</button>
        <div id="search_autocomplete" class="search-autocomplete"></div>
        <script type="text/javascript">
            var searchForm = new Varien.searchForm('search_mini_form', 'search', '');
            searchForm.initAutocomplete('/catalogsearch/ajax/suggest/', 'search_autocomplete');
        </script>
        <input type="hidden" id="form__token-uid-556af114b2701" name="form[_token]" data-ftid="form__token" value="9bd7b70c4218e3130d0deee54047a7a8b466531e">
    </form>
</div>
```

Extending exiting block types
----------------------------------

Currently the [LinkType](../../Layout/Block/Type/LinkType.php) does not support adding an image inside the `<a />` tag.
For our example, we'll extend this block type to have such possibility.
We create a `LinkExtension` class in place it in: `Acme/Bundle/LayoutBundle/Layout/Block/Extension` dir.

```php
namespace Acme\Bundle\LayoutBundle\Layout\Block\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\LinkType;

/**
 * This extension extends links with "image" option, that
 * can be used to add an image inside the link tag.
 */
class LinkExtension extends AbstractBlockTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['image']);
    }
    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        if (!empty($options['image'])) {
            $view->vars['image'] = $options['image'];
        }
    }
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return LinkType::NAME;
    }
}
```
And also register it in our container:

```yaml
    acme_layout.block_type_extension.link:
        class: Acme\Bundle\LayoutBundle\Layout\Block\Extension\LinkExtension
        tags:
            - { name: layout.block_type_extension, alias: link }
```

Now we can customize the twig template for the link block by adding the following lines in the block theme file:

```twig
{% block link_widget -%}
    <a{{ block('block_attributes') }} href="{{ path is defined ? path : path(route_name, route_parameters) }}">
        {%- if icon is defined %}{{ block('icon_block') }}{% endif %}
        {%- if text is defined %}{{ text|block_text(translation_domain) }}{% endif -%}
        {# Render image if defined #}
        {%- if image is defined %}{{ block('image_block') }}{% endif %}
    </a>
{%- endblock %}

{% block image_block -%}
    <img src={{ image }}{% if image_class is defined %} class="{{ image_class }}"{% endif %}{% if image_alt is defined %} alt="{{ image_alt }}"{% endif %} />
{%- endblock %}
```

Positioning of blocks in layout
-----------------------------------

The layout engine lets you add or move blocks into any position by specifying the `siblingId`. For example, we can add a logo image into our header block before the navigation block:

```yaml
    - @add:
        id : logo
        parentId: header
        blockType: link
        options:
            image: logo.png
            path: /
            attr:
                class: logo
            vars:
                image_class: large
                image_alt: Madison Island
        siblingId: navigation
        prepend: true
```

Note that if `prepend` was false (by default it is) the logo would be placed right after the navigation block.
The same positioning can be achieved using `move` action. Let's move our language switcher before the header block:
```yaml
    - @move:
        id: lang_switch
        parentId: page_container
        siblingId: ~
        prepend: true
```
Note that if `siblingId` is not specified the block will be positioned an

Working with lists
-----------------------------------
As an example, we'll be adding navigation menu to the page using both ordered and unordered lists.

In layout update file we do the following:
```yaml
layout:
    actions:
        - @addTree:
            items:
                nav_container:
                    blockType: container
                nav_category_list:
                    blockType: ordered_list
                    options:
                        attr:
                            class: nav-primary
                nav_women_category:
                    blockType: list_item
                    options:
                        attr:
                            class: parent
                nav_women_category_link:
                    blockType: link
                    options:
                        path: /women.html
                        text: Women
                        attr:
                            class: level0 has-children
                nav_women_subcategory_list:
                    blockType: list
                    options:
                        attr:
                            class: level0
                nav_women_all_subcategory:
                    blockType: link
                    options:
                        path: /women.html
                        text: View All Women
                        attr:
                            class: level1
                nav_women_new_subcategory:
                    blockType: link
                    options:
                        path: /women/new-arrivals.html
                        text: New Arrivals
                        attr:
                            class: level1
            tree:
                navigation:
                    nav_container:
                        nav_category_list:
                            nav_women_category:
                                nav_women_category_link: ~
                                nav_women_subcategory_list:
                                    nav_women_all_subcategory: ~
                                    nav_women_new_subcategory: ~
```

Note that we can use `list_item` block type to be able to add custom attributes (e.g. `class`) to the `<li>` tag and add child blocks.
For the list items with no children we can add any other block tyle, `link` in our example, and it will be wrapped into the `<li>` tag.

So our rendered HTML will look like this:
```html
<nav id="nav">
    <ol class="nav-primary">
        <li class="parent">
            <a class="level0 has-children" href="/women.html">Women</a>
            <ul class="level0">
                <li><a class="level1" href="/women.html">View All Women</a></li>
                <li><a class="level1" href="/women/new-arrivals.html">New Arrivals</a></li>
            </ul>
        </li>
    </ol>
</nav>
```

Note: to customize the `nav_container` block to be rendered in `<nav>` tag we need to add a template in the block theme file:
```twig
{% block _nav_container_widget %}
    <nav id="nav">
        {{ block_widget(block) }}
    </nav>
{% endblock %}
```

Bredcrumbs is a special case of the list where items are separated by some symbol. We can customize rendering of the list by adding the following template to our block theme:
```twig
{% block _breadcrumbs_widget -%}
    <div class="breadcrumbs">
        <ul>
        {% for child in block -%}
            {% if child.vars.visible -%}
                {% if not loop.last %}
                    <li>{{ block_widget(child) }}<span>/ </span></li>
                {%- else -%}
                    <li><strong>{{ block_widget(child) }}</strong></li>
                {% endif %}
            {%- endif %}
        {%- endfor %}
        </ul>
    </div>
{%- endblock %}
```

Now we can place the block with `breadcrumbs` Id in our layout update and add some child elements into it:
```yaml
layout:
    actions:
        - @add:
            id : breadcrumbs
            parentId: main_container
            blockType: list
            siblingId: ~
            prepend: true
        - @add:
            id : breadcrumbs_home
            parentId: breadcrumbs
            blockType: link
            options:
                path: /
                text: Home
                attr:
                    title: Go to Home Page
        - @add:
            id : breadcrumbs_product
            parentId: breadcrumbs
            blockType: text
            options:
                text: { @value: $data.product.name }
```

This should render into the following HTML:
```html
<div class="breadcrumbs">
    <ul>
        <li><a title="Go to Home Page" href="/">Home</a><span>/ </span></li>
        <li><strong>Chelsea Tee</strong></li>
    </ul>
</div>
```

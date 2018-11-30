# Layout Context

The **layout context** is an object that holds data shared between different components of the **layout** (such as layout updates, extensions, block types etc). Be aware that data that you put in the layout context is some kind of configuration (or static) data, and it means that two layouts built on the same context are the same, too.

As an example, assume that you need to build a layout for a Product Details page. All product pages should be similar (e.g. have the same menu placement, form fields, etc.), except for the product name and the description. Let us also assume that you have an option that specifies that a menu should be rendered either on the top or on the left of the page. 

In this case, it would be reasonable to put the menu position option in the layout context. It would not, however, be a very good idea to put the product object in the layout context. The reason for it is that it will not be possible to reuse the same layout for different products, and you will have to build a new layout for each product. 

Sharing dynamic data, like a product object, is described in the [Layout Data](./layout_data.md) topic. 

If there are several types of products, and their details pages (e.g. groceries, stationary, and toys) are supposed to differ significantly, it would be reasonable to put the product type in the layout context.

```php
$layoutContext = new LayoutContext();
$layoutBuilder = $layoutManager->getLayoutBuilder();
$layoutBuilder->getLayout($layoutContext);
```

## Types of Data in the Context

The layout context can hold any types of data, including scalars, arrays and objects. But any object you want to put in the context must implement the [ContextItemInterface](../../../../Component/Layout/ContextItemInterface.php). 

## Accessing Context

Context can be accessed in a few ways. The most common of them are:
 
 - Accessing context from the [BlockInterface](../../../../Component/Layout/BlockInterface.php) instance. For example, when you need to get values from context when building the view.
 
   For example:

   ```php
    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        $value = $block->getContext()->get('value-key');
    }
   ```
   
 - Accessing context using the [Symfony expression component](http://symfony.com/doc/current/components/expression_language/introduction.html) by providing  expression as an option for some block.
 
   For example:

   ```yaml
    actions:
        ...
        - '@add':
            id: blockId
            parentId: parentId
            blockType: typeName
            options:
                optionName: '=context["valueKey"]
   ```
   

## Context Configurators

It might be required to configure the context based on the current application state, client setting, or to define the 
default values, etc. In order to prevent copypasting of the boilerplate code, **context configurators** have been introduced.
Each configurator should implement the [ContextConfiguratorInterface](../../../../Component/Layout/ContextConfiguratorInterface.php), and be registered as a service in the DI container with the `layout.context_configurator` tag. 

For debugging purposes, the `oro:layout:debug --context` command has been added. It allows to see how the context data-resolver will
be configured by the **context configurators**.

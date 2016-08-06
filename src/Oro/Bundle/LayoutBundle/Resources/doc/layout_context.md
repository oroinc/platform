Layout context
==============

The **layout context** is an object that holds data shared between different components of the **layout** (such as layout updates, extensions, block types etc). It is important to understand that data you put in the layout context is some kind of configuration (or static) data, it means that two layouts built on the same context are the same as well.

Let's consider this on an example. Imagine that you need to build a layout for a Product Details page. All the product pages shall be similar (e.g. have the same menu placement, form fields, etc.) except for the specific product name and description. Also imagine that you have an option that specifies that a menu should be rendered either on the top or on the left of the page. In this case, it is reasonable to put the menu position option in the layout context, but it is not very good idea to put the product object in the layout context, because in this case it will not be possible to reuse the same layout for different products and you will have to build new layout for each product. Sharing of dynamic data, like a product object, is described in [Layout Data](./layout_data.md) topic. On the other hand, if there are several types of products and their details pages (e.g. groceries, stationary and toys) shall differ significantly, it is reasonable to put the product type in the layout context.


```php
$layoutContext = new LayoutContext();
$layoutBuilder = $layoutManager->getLayoutBuilder();
$layoutBuilder->getLayout($layoutContext);
```

Types of data in the context
----------------------------

The layout context can hold any types of data, including scalars, arrays and objects. But any object you want to put in the context must implement [ContextItemInterface](../../../../Component/Layout/ContextItemInterface.php). 

Accessing context
-----------------

There are few ways how context could be accessed. Most common ways are the following:
 
 - Access context from the [BlockInterface](../../../../Component/Layout/BlockInterface.php) instance. For example, when it is needed to get values from context during view building.
   Example:

   ```php
    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $value = $block->getContext()->get('value-key');
    }
   ```
   
 - Access context using [Symfony expression component](http://symfony.com/doc/current/components/expression_language/introduction.html) by providing 
   expression as an option for some block.
   Example:

   ```yaml
    actions:
        ...
        - @add:
            id: blockId
            parent: parentId
            blockType: typeName
            options:
                optionName: '=context["valueKey"]
   ```
   

Context configurators
---------------------

It might be required to configure the context based on current application state, client setting or just define 
default values and so on. In order to prevent copy paste of boilerplate code **context configurators** have been introduced.
Each configurator should implement [ContextConfiguratorInterface](../../../../Component/Layout/ContextConfiguratorInterface.php) and be registered as a service 
in DI container with the tag `layout.context_configurator`. 

For debugging purposes `oro:layout:debug --context` command has been added, it allows to see how the context data-resolver will
be configured by **context configurators**.

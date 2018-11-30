# Layout data

In most cases, you need use the same layout to show different data. For example, the same layout can be used to show different products. To achieve this, you need a way to get data and to bind them to layout elements.

This topic describes what the layout data are and how to work with them. Please make sure that you are familiar with the [layout context](layout_context.md) before proceeding to the layout data.

## Types of Data Providers

You can provide data for layouts in two ways:


- By adding them to the `data` collection of the [layout context](../../../../Component/Layout/ContextInterface.php). This method can be used for page specific data, or the data retrieved from the HTTP request.
- By creating a standalone data provider. This method is useful if data is used on many pages and the data source is a database, HTTP session, external web service, etc.

## Using the Layout Context as Data Provider


If you want to add some data to the layout context you can use `data` method of [ContextInterface](../../../../Component/Layout/ContextInterface.php). This method returns an instance of [ContextDataCollection](../../../../Component/Layout/ContextDataCollection.php). Use `set` method of this collection to add data:

```php
$context->data()->set(
	'widget_id',
	$request->query->get('_wid')
);
```

The `set` method has the following arguments:

- `$name` - A string which can be used to access the data.
- `$value` - The data. The data can be any type, for example an array, object or some scalar type.

Also you can create a [layout context configurator](layout_context.md#context-configurators) to set default data:

```php
$context->data()->setDefault(
    'widget_id',
    function () {
        if (!$this->request) {
            throw new \BadMethodCallException('The request expected.');
        }

        return $this->request->query->get('_wid');
    }
);
```

The `setDefault` method has the following arguments:

- `$name` - A string which can be used to access the data.
- `$value` - The data. The data can be any type, for example an array, object or some scalar type. You can also use the callback method to get the data. The callback definition is as follows: `function (array|\ArrayAccess $options) : mixed`, where the `$options` argument represents the context variables.


## Defining a Data Provider

As example, consider a data provider that returns product details:

```php
namespace Acme\Bundle\ProductBundle\Layout\Extension;

use Acme\Bundle\ProductBundle\Entity\Product;

class ProductDataProvider
{
    /**
     * @param Product $product
     */
    public function getCode(Product $product)
    {
        return $product->getId();
    }
}
```

You can also implement the [AbstractFormProvider](../../Layout/DataProvider/AbstractFormProvider.php) if you use forms.

**IMPORTANT:** The DataProvider provider method should begin with `get`, `has` or `is`.

## Registering a Data Provider

To make the layout engine aware of your data provider, register it as a service in the DI container with the `layout.data_provider` tag:

```yaml
acme_product.layout.data_provider.product:
    class: Acme\Bundle\ProductBundle\Layout\DataProvider\ProductProvider
    tags:
        - { name: layout.data_provider, alias: product }
```

The `alias` key of the tag is required and should be unique for each data provider. This alias is used to get the data provider from the registry.

## Accessing Data

There are few ways to access data. The most common ways are:
 
 - Accessing data from the [BlockInterface](../../../../Component/Layout/BlockInterface.php) instance. For example, when you need to get data when building the view.
 
   Example:

   ```php
    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
	    /** @var Product $product */
        $product = $block->getData()->get('product');
        $productCode = $product->getCode();
    }
   ```
   
 - Accessing data using the [Symfony expression component](http://symfony.com/doc/current/components/expression_language/introduction.html) by providing the expression as an option for a block.
 
   Example:

   ```yaml
    actions:
        ...
        - '@add':
            id: product_code
            parentId: product_details
            blockType: text
            options:
                text: '=data["product"].getCode()'
   ```

The way how you access the data does not depend on where the data are located, in the layout context or in the standalone data provider. But it is important to remember that standalone data providers have higher priority than data from the layout context. It means that if there are data with the same alias in both the layout context and a standalone data provider registry, the standalone data provider will be used.

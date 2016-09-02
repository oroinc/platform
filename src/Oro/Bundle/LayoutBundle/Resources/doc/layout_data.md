Layout data
-----------

In most cases you need use the same layout to show different data. For example, the same layout can be used to show different products. To achieve this you need a way to get data and to bind them to layout elements.
This topic describes what the layout data are and how to work with them. Please make sure that you are familiar with the [layout context](layout_context.md) before learning of the layout data.

Data providers types
--------------------

There are two ways how to provide data for layouts:

- Add them to `data` collection of the [layout context](../../../../Component/Layout/ContextInterface.php). This method can be used for page specific data or the data retrieved from the HTTP request.
- Create standalone data provider. This method is useful if some data is used on many pages and the data source is a database, HTTP session, external web service, etc.

Using the layout context as data provider
-----------------------------------------

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
- `$value` - The data. The data can be any type, for example an array, object or some scalar type. Also you can use the callback method to get the data. The callback definition: `function (array|\ArrayAccess $options) : mixed`, where where `$options` argument represents the context variables.


Defining a data provider
------------------------

As example, let's consider a data provider that returns product details:

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

Also you can implement [AbstractFormProvider](../../../../Component/Layout/DataProvider/AbstractFormProvider.php) if you use forms.

**IMPORTANT:**  DataProvider provider method should begin with `get`, `has` or `is`.

Registering a data provider
---------------------------

To make the layout engine aware of your data provider it should be registered as a service in DI container with the tag `layout.data_provider`:

```yaml
acme_product.layout.data_provider.product:
    class: Acme\Bundle\ProductBundle\Layout\DataProvider\ProductProvider
    tags:
        - { name: layout.data_provider, alias: product }
```

The `alias` key of the tag is required and should be unique for each data provider. This alias is used to get a data provider from the registry.

Accessing data
--------------

There are few ways how data could be accessed. Most common ways are the following:
 
 - Access data from the [BlockInterface](../../../../Component/Layout/BlockInterface.php) instance. For example, when it is needed to get data during view building.
   Example:

   ```php
    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
	    /** @var Product $product */
        $product = $block->getData()->get('product');
        $productCode = $product->getCode();
    }
   ```
   
 - Access data using [Symfony expression component](http://symfony.com/doc/current/components/expression_language/introduction.html) by providing 
   expression as an option for some block.
   Example:

   ```yaml
    actions:
        ...
        - @add:
            id: product_code
            parent: product_details
            blockType: text
            options:
                text: '=data["product"].getCode()'
   ```

The way how you access the data does not depend on where the data are located, in the layout context or in the standalone data provider. But it is important to remember that standalone data providers have higher priority than data from the layout context. It means that if there are data with the same alias in both the layout context and a standalone data provider registry, the standalone data provider will be used.

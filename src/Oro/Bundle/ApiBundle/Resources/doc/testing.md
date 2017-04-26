Tesitng REST Api
================

Load Fixtures
-------------

You can use php and Alice fixtures as well:

```php
class InventoryLevelApiTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures(['@OroInventoryBundle/Tests/Functional/DataFixtures/inventory_level.yml']);
    }
```
Fixture file:
```yml
dependencies:
  - Oro\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehouseAndInventoryLevels

Oro\Bundle\InventoryBundle\Entity\InventoryLevel:
  warehouse_inventory_level.warehouse.1.product_unit_precision.product-1.primary_unit:
    warehouse: '@warehouse.1'
    productUnitPrecision: '@product-1->primaryUnitPrecision'
    quantity: 10
```

Use dependencies to other alice fixtures or php fixtures under ```dependencies``` key
References will be shared between alice and doctrine fixtures

Alice references
----------------

In alice fixtures as well as in yml templates alice references can be used.
```
@product-1
```

Use methods of properties with references:

```
@product-2->createdAt->format("Y-m-d\TH:i:s\Z")
```

Yaml templates
--------------

Yaml template is a regular yaml. The only difference is that you can use references and faker in values
All values will processing by Alice and replace to appropriate value.
See Alice documentation - https://github.com/nelmio/alice/blob/master/doc/relations-handling.md#references

Assert expectations
-------------------

Assert expected response by using yaml templates.
Yaml template:

```yml
data:
    -
        type: inventorylevels
        id: '@warehouse_inventory_level.warehouse.1.product_unit_precision.product-1.liter->id'
        attributes:
            quantity: '10.0000000000'
        relationships:
            product:
                data:
                    type: products
                    id: '@product-1->id'
            productUnitPrecision:
                data:
                    type: productunitprecisions
                    id: '@product_unit_precision.product-1.liter->id'
            warehouse:
                data:
                    type: warehouses
                    id: '@warehouse.1->id'
# there is long response
```
In php test:

```php
public function testCgetEntity()
{
    $parameters = [
        'include' => 'product,productUnitPrecision',
        'filter' => [
            'product.sku' => '@product-1->sku',
        ]
    ];
    $entityType = $this->getEntityType(InventoryLevel::class);
    $response = $this->cget(['entity' => $entityType], $parameters);
    $this->assertResponseContains(
        '@OroWarehouseBundle/Tests/Functional/Api/responses/cget_filter_by_product.yml',
        $response
    );
}
```

Yaml templates for request body
-------------------------------

You can use array with references for request body:
```php
public function testUpdateEntity()
{
    $entityType = $this->getEntityType(InventoryLevel::class);
    $body = [
        'data' => [
            'type' => 'inventorylevels',
            'id' => '<toString(@product-1->id)>',
            'attributes' => [
                'quantity' => '17'
            ]
        ],
    ];
    $response = $this->patch(
        ['entity' => $entityType, 'product.sku' => '@product-1->sku'],
        $body
    );
}
```

or you can hold yaml in ```.yml``` file:

```php
public function testCreateCustomer()
{
    $this->post(
        ['entity' => $this->getEntityType(Customer::class)],
        __DIR__.'/requests/create_customer.yml'
    );
}
```

Process single reference
------------------------

Sometimes you need a process a single reference e.g. for compare it with other value
```php
self::processTemplateData('@inventory_level.product_unit_precision.product-1.liter->quantity')
```
processTemplateData method can process string, array or yml file.


Dump repsonse into Yaml template
--------------------------------

During development new tests for REST api you have ability to dump response into Yaml template
```php
public function testCgetEntity()
{
    $entityType = $this->getEntityType(Product::class);
    $response = $this->get(
        'oro_rest_api_cget',
        ['entity' => $entityType],
        ['filter' => ['sku' => '@product-1->sku']]
    );
    $this->dumpYmlTemplate(__DIR__.'/responses/test_cget_entity.yml', $response);
}
```
Use this for the first time and check references after that - there are can be some collision 
with references that has same ids

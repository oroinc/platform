Testing REST Api
================

Table of Contents
-----------------
   - [Overview](#overview)
   - [Load Fixtures](#load-fixtures)
   - [Alice references](#alice-references)
   - [Yaml templates](#yaml-templates)
   - [Assert expectations](#assert-expectations)
   - [Yaml templates for request body](#yaml-templates-for-request-body)
   - [Process single reference](#process-single-reference)
   - [Dump response into Yaml template](#dump-response-into-yaml-template)

Overview
--------

To be sure that your REST API resources work properly you can cover them by [functional tests](https://www.orocrm.com/documentation/current/book/functional-tests). To simplify creating the functional test for REST API resources conform [JSON.API specification](http://jsonapi.org/format/) the [RestJsonApiTestCase](../../Tests/Functional/RestJsonApiTestCase.php) test case was created. The following table contains the list of most useful methods of this class:

| Method | Description |
| --- | --- |
| request | Sends any REST API request. |
| cget | Sends GET request for a list of entities. See [get_list action](./actions.md#get_list-action). |
| get | Sends GET request for a single entity. See [get action](./actions.md#get-action). |
| post | Sends POST request for an entity resource. See [create action](./actions.md#create-action). |
| patch | Sends PATCH request for a single entity. See [update action](./actions.md#update-action). |
| delete | Sends DELETE request for a single entity. See [delete action](./actions.md#delete-action). |
| cdelete | Sends DELETE request for a list of entities. See [delete_list action](./actions.md#delete_list-action). |
| getSubresource | Sends GET request for a sub-resource of a single entity. See [get_subresource action](./actions.md#get_subresource-action). |
| getRelationship | Sends GET request for a relationship of a single entity. See [get_relationship action](./actions.md#get_relationship-action). |
| postRelationship | Sends POST request for a relationship of a single entity. See [add_relationship action](./actions.md#add_relationship-action). |
| patchRelationship | Sends PATCH request for a relationship of a single entity. See [update_relationship action](./actions.md#update_relationship-action). |
| assertResponseContains | Asserts the response content contains the the given data. If the first parameter is a file name, the file should be located in the "responses" directory near to PHP file contains the test. |
| assertResponseCount | Asserts the response contains the given number of data items. |
| assertResponseNotEmpty | Asserts the response data are not empty. |
| dumpYmlTemplate | Saves a response content to a YAML file. If the first parameter is a file name, the file should be located in the "responses" directory near to PHP file contains the test. |

Load Fixtures
-------------

You can use [Doctrine and Alice fixtures](https://www.orocrm.com/documentation/current/book/functional-tests#loading-data-fixtures):

```php
class InventoryLevelApiTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([__DIR__ . '/DataFixtures/inventory_level.yml']);
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

The ```dependencies``` section can be used if a fixture depends to other Doctrine or Alice fixtures.
References will be shared between Alice and Doctrine fixtures.

Alice references
----------------

In Alice fixtures as well as in yml templates the references can be used.

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
All values will be processed by Alice and replaces with appropriate value.
For details see [Alice documentation](https://github.com/nelmio/alice/blob/master/doc/relations-handling.md#references).

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
```

In php test:

```php
public function testGetList()
{
    $response = $this->cget(
        ['entity' => 'inventorylevels'],
        [
            'include' => 'product,productUnitPrecision',
            'filter' => [
                'product.sku' => '@product-1->sku',
            ]
        ]
    );

    $this->assertResponseContains('cget_filter_by_product.yml', $response);
}
```

Yaml templates for request body
-------------------------------

You can use array with references for request body:

```php
public function testUpdateEntity()
{
    $response = $this->patch(
        ['entity' => 'inventorylevels', 'product.sku' => '@product-1->sku'],
        [
            'data' => [
                'type' => 'inventorylevels',
                'id' => '<toString(@product-1->id)>',
                'attributes' => [
                    'quantity' => '17'
                ]
            ],
        ]
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

The `processTemplateData` method can process string, array or yml file.


Dump response into Yaml template
--------------------------------

During development new tests for REST api you have ability to dump response into Yaml template

```php
public function testGetList()
{
    $response = $this->cget(
        ['entity' => 'products'],
        ['filter' => ['sku' => '@product-1->sku']]
    );
    // dumps response content to __DIR__ . '/responses/' . 'test_cget_entity.yml'
    $this->dumpYmlTemplate('test_cget_entity.yml', $response);
}
```

Use this for the first time and check references after that - there are can be some collision
with references that has same ids

# Testing REST Api

   - [Overview](#overview)
   - [Load Fixtures](#load-fixtures)
   - [Alice references](#alice-references)
   - [Yaml templates](#yaml-templates)
   - [Assert expectations](#assert-expectations)
   - [Yaml templates for request body](#yaml-templates-for-request-body)
   - [Process single reference](#process-single-reference)
   - [Dump response into Yaml template](#dump-response-into-yaml-template)

## Overview

To ensure that your REST API resources work properly, cover them by [functional tests](https://oroinc.com/doc/orocrm/current/book/functional-tests). 

To simplify creation of the functional test for REST API resources that conform [JSON.API specification](http://jsonapi.org/format/), the [RestJsonApiTestCase](../../Tests/Functional/RestJsonApiTestCase.php) test case was created. The following table contains the list of the most useful methods of this class:

| Method | Description |
| --- | --- |
| request | Sends a REST API request. |
| options | Sends the OPTIONS request. See [options](./actions.md#options-action). |
| cget | Sends the GET request for a list of entities. See [get_list action](./actions.md#get_list-action). |
| get | Sends the GET request for a single entity. See [get action](./actions.md#get-action). |
| post | Sends the POST request for an entity resource. See [create action](./actions.md#create-action). If the second parameter is a file name, the file should be located in the `requests` directory next to the PHP file that contains the test. |
| patch | Sends the PATCH request for a single entity. See [update action](./actions.md#update-action). If the second parameter is a file name, the file should be located in the `requests` directory next to the PHP file that contains the test. |
| delete | Sends the DELETE request for a single entity. See [delete action](./actions.md#delete-action). |
| cdelete | Sends the DELETE request for a list of entities. See [delete_list action](./actions.md#delete_list-action). |
| getSubresource | Sends the GET request for a sub-resource of a single entity. See [get_subresource action](./actions.md#get_subresource-action). |
| postSubresource | Sends the POST request for a sub-resource of a single entity. See [add_relationship action](./actions.md#add_subresource-action). |
| patchSubresource | Sends the PATCH request for a sub-resource of a single entity. See [update_relationship action](./actions.md#update_subresource-action). |
| deleteSubresource | Sends the DELETE request for a sub-resource of a single entity. See [delete_relationship action](./actions.md#delete_subresource-action). |
| getRelationship | Sends the GET request for a relationship of a single entity. See [get_relationship action](./actions.md#get_relationship-action). |
| postRelationship | Sends the POST request for a relationship of a single entity. See [add_relationship action](./actions.md#add_relationship-action). |
| patchRelationship | Sends the PATCH request for a relationship of a single entity. See [update_relationship action](./actions.md#update_relationship-action). |
| deleteRelationship | Sends the DELETE request for a relationship of a single entity. See [delete_relationship action](./actions.md#delete_relationship-action). |
| updateResponseContent | Replaces all values in the given expected response content with corresponding value from the actual response content when the key of an element is equal to the given key and the value of this element is equal to the given placeholder. If the first parameter is a file name, the file should be located in the `responses` directory next to the PHP file that contains the test. |
| assertResponseContains | Asserts that the response content contains the given data. If the first parameter is a file name, the file should be located in the `responses` directory next to the PHP file that contains the test. |
| assertResponseCount | Asserts that the response contains the given number of data items. |
| assertResponseNotEmpty | Asserts that the response data are not empty. |
| assertResponseValidationError | Asserts that the response content contains the given validation error. |
| assertResponseValidationErrors | Asserts that the response content contains the given validation errors. |
| assertAllowResponseHeader | Asserts "Allow" response header equals to the expected value. |
| assertMethodNotAllowedResponse | Asserts response status code equals to 405 (Method Not Allowed) and "Allow" response header equals to the expected value. |
| dumpYmlTemplate | Saves a response content to a YAML file. If the first parameter is a file name, the file is saved into the `responses` directory next to the PHP file that contains the test. |
| getResourceId | Extracts the JSON.API resource identifier from the response. For details, see [JSON.API Specification](http://jsonapi.org/format/#document-resource-objects). |
| getNewResourceIdFromIncludedSection | Extracts the JSON.API resource identifier from the "included" section of the response. For details, see [Create and Update Related Resources Together with a Primary API Resource](https://oroinc.com/orocrm/doc/current/dev-guide/web-api#create-and-update-related-resources-together-with-a-primary-api-resource). |
| getRequestData | Converts the given request to an array that can be sent to the server. The given request can be a path to a file that contains the request data or an array with the request data. If the request is a file name, the file should be located in the `requests` directory next to the PHP file that contains the test. |
| getResponseErrors | Extracts the list of errors from the JSON.API response. For details, see [JSON.API Specification](http://jsonapi.org/format/#errors). |
| getApiBaseUrl | Returns the base URL for all REST API requests, e.g. `http://localhost/api`. |
| appendEntityConfig | Appends a configuration of an entity. This method is helpful when you create a general functionality and need to test it for different configurations without creating a test entity for each of them. Please note that the configuration is restored after each test and thus, you do not need to do it manually. |

**Notes**:

 - By default HATEOAS is disabled in functional tests, although it is enabled by default in production
   and API Sandbox. It was done to avoid cluttering up the tests with HATEOAS links. In case you want to enable
   HATEOAS for your test, use `HTTP_HATEOAS` server parameter,
   e.g. `$this->cget(['entity' => 'products']), [], ['HTTP_HATEOAS' => true]`.

## Load Fixtures

You can use [Doctrine and Alice fixtures](https://oroinc.com/doc/orocrm/current/book/functional-tests#loading-data-fixtures):

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

**Fixture file:**

```yml
dependencies:
  - Oro\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehouseAndInventoryLevels

Oro\Bundle\InventoryBundle\Entity\InventoryLevel:
  warehouse_inventory_level.warehouse.1.product_unit_precision.product-1.primary_unit:
    warehouse: '@warehouse.1'
    productUnitPrecision: '@product-1->primaryUnitPrecision'
    quantity: 10
```

The ```dependencies``` section can be used if a fixture depends on another Doctrine or Alice fixtures.
References are shared between Alice and Doctrine fixtures.

## Alice References

You can use references in Alice fixtures.

```
@product-1
```

Use methods of properties with references:

```
@product-2->createdAt->format("Y-m-d\TH:i:s\Z")
```

## YAML Templates

A YAML template is a regular YAML file. The only difference is that you can use references and fakers in values.
They will be processed by Alice and replaces with the appropriate real values.
For details, see the [Alice documentation](https://github.com/nelmio/alice/blob/master/doc/relations-handling.md#references).

## Assert the Expectations

Assert the expected response by using YAML templates.

A YAML template:

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

In a PHP test:

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

## YAML Templates for a Request Body

You can use an array with references for a request body:

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

Alternatively, you can store YAML in a ```.yml``` file:

```php
public function testCreateCustomer()
{
    $this->post(
        ['entity' => 'customers'],
        'create_customer.yml' // loads data from __DIR__ . '/requests/create_customer.yml'
    );
}
```

## Process Single Reference

To process a single reference, e.g. to compare it with other value:

```php
self::processTemplateData('@inventory_level.product_unit_precision.product-1.liter->quantity')
```

The `processTemplateData` method can process a string, an array, or a YAML file.

## Dump the Response into a YAML Template

When you develop new tests for REST API, it may be convenient to dump responses into a YAML template:

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

**Important:**
Do not forget to check references after you dump a response for the first time: there can be collisions
if references have the same IDs.

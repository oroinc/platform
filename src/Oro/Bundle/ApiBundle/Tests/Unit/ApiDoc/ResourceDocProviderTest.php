<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;

class ResourceDocProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ResourceDocProvider */
    private $resourceDocProvider;

    protected function setUp(): void
    {
        $this->resourceDocProvider = new ResourceDocProvider();
    }

    /**
     * @dataProvider getResourceDescriptionProvider
     */
    public function testGetResourceDescription(string $action, string $entityDescription, ?string $expected)
    {
        self::assertSame(
            $expected,
            $this->resourceDocProvider->getResourceDescription($action, $entityDescription)
        );
    }

    public function getResourceDescriptionProvider(): array
    {
        return [
            ['unknown', 'Product', null],
            [ApiAction::OPTIONS, 'Product', 'Get options'],
            [ApiAction::GET, 'Product', 'Get Product'],
            [ApiAction::GET_LIST, 'Products', 'Get Products'],
            [ApiAction::UPDATE, 'Product', 'Update Product'],
            [ApiAction::UPDATE_LIST, 'Products', 'Create or update a list of Products'],
            [ApiAction::CREATE, 'Product', 'Create Product'],
            [ApiAction::DELETE, 'Product', 'Delete Product'],
            [ApiAction::DELETE_LIST, 'Products', 'Delete Products']
        ];
    }

    /**
     * @dataProvider getResourceDocumentationProvider
     */
    public function testGetResourceDocumentation(
        string $action,
        string $entitySingularName,
        string $entityPluralName,
        ?string $expected
    ) {
        self::assertSame(
            $expected,
            $this->resourceDocProvider->getResourceDocumentation($action, $entitySingularName, $entityPluralName)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getResourceDocumentationProvider(): array
    {
        return [
            ['unknown', 'Product', 'Products', null],
            [ApiAction::OPTIONS, 'Product', 'Products', 'Get communication options for a resource'],
            [ApiAction::GET, 'Product', 'Products', 'Get an entity'],
            [ApiAction::GET_LIST, 'Product', 'Products', 'Get a list of entities'],
            [ApiAction::UPDATE, 'Product', 'Products', 'Update an entity'],
            [
                ApiAction::UPDATE_LIST,
                'Product',
                'Products',
                'Create or update a list of product records.'
                . "\n\n"
                . 'The request is processed asynchronously, and the details of the corresponding asynchronous operation'
                . "\nare returned in the response."
                . "\n\n"
                . '**Note:** *The server may process records in any order regardless of the order'
                . "\nin which they are specified in the request.*"
                . "\n\n"
                . 'The input data for each record is the same as for the API resources to create or update'
                . "\na single product record."
                . "\n\n"
                . 'Example:'
                . "\n\n"
                . '```JSON'
                . "\n{"
                . "\n   \"data\": ["
                . "\n      {"
                . "\n          \"type\":\"entityType\","
                . "\n          \"attributes\": {...},"
                . "\n          \"relationships\": {...}"
                . "\n      },"
                . "\n      {"
                . "\n          \"type\":\"entityType\","
                . "\n          \"attributes\": {...},"
                . "\n          \"relationships\": {...}"
                . "\n       }"
                . "\n   ]"
                . "\n}"
                . "\n```"
                . "\n\n"
                . 'Use the **update** meta property to mark the records that should be updated.'
                . "\nSee [Creating and Updating Related Resources with Primary API Resource]"
                . '(https://doc.oroinc.com/api/create-update-related-resources/)'
                . "\nfor more details about this meta property."
                . "\n\n"
                . 'Example:'
                . "\n\n"
                . '```JSON'
                . "\n{"
                . "\n   \"data\": ["
                . "\n      {"
                . "\n          \"meta\": {\"update\": true},"
                . "\n          \"type\":\"entityType\","
                . "\n          \"id\": \"1\","
                . "\n          \"attributes\": {...},"
                . "\n          \"relationships\": {...}"
                . "\n      },"
                . "\n      {"
                . "\n          \"meta\": {\"update\": true},"
                . "\n          \"type\":\"entityType\","
                . "\n          \"id\": \"2\","
                . "\n          \"attributes\": {...},"
                . "\n          \"relationships\": {...}"
                . "\n       }"
                . "\n   ]"
                . "\n}"
                . "\n```"
                . "\n"
                . "\nThe related entities can be created or updated when processing primary entities."
                . "\nThe list of related entities should be specified in the **included** section"
                . "\nthat must be placed at the root level, the same as the **data** section."
                . "\n\n"
                . 'Example:'
                . "\n\n"
                . '```JSON'
                . "\n{"
                . "\n   \"data\": ["
                . "\n      {"
                . "\n          \"type\":\"entityType\","
                . "\n          \"attributes\": {...},"
                . "\n          \"relationships\": {"
                . "\n              \"relation\": {"
                . "\n                  \"data\": {"
                . "\n                      \"type\":\"entityType1\","
                . "\n                      \"id\": \"included_entity_1\""
                . "\n                  }"
                . "\n              },"
                . "\n              ..."
                . "\n          }"
                . "\n      },"
                . "\n      ..."
                . "\n   ],"
                . "\n   \"included\": ["
                . "\n       {"
                . "\n          \"type\":\"entityType1\","
                . "\n          \"id\": \"included_entity_1\","
                . "\n          \"attributes\": {...},"
                . "\n          \"relationships\": {...}"
                . "\n      },"
                . "\n      ..."
                . "\n   ]"
                . "\n}"
                . "\n```"
            ],
            [ApiAction::CREATE, 'Product', 'Products', 'Create an entity'],
            [ApiAction::DELETE, 'Product', 'Products', 'Delete an entity'],
            [ApiAction::DELETE_LIST, 'Product', 'Products', 'Delete a list of entities']
        ];
    }

    /**
     * @dataProvider getSubresourceDescriptionProvider
     */
    public function testGetSubresourceDescription(
        string $action,
        string $associationDescription,
        bool $isCollection,
        ?string $expected
    ) {
        self::assertSame(
            $expected,
            $this->resourceDocProvider->getSubresourceDescription($action, $associationDescription, $isCollection)
        );
    }

    public function getSubresourceDescriptionProvider(): array
    {
        return [
            ['unknown', 'test', false, null],
            [ApiAction::OPTIONS, 'test', false, 'Get options'],
            [ApiAction::OPTIONS, 'test', true, 'Get options'],
            [ApiAction::GET_SUBRESOURCE, 'test', false, 'Get test'],
            [ApiAction::GET_SUBRESOURCE, 'test', true, 'Get test'],
            [ApiAction::UPDATE_SUBRESOURCE, 'test', false, 'Update test'],
            [ApiAction::UPDATE_SUBRESOURCE, 'test', true, 'Update test'],
            [ApiAction::ADD_SUBRESOURCE, 'test', false, 'Add test'],
            [ApiAction::ADD_SUBRESOURCE, 'test', true, 'Add test'],
            [ApiAction::DELETE_SUBRESOURCE, 'test', false, 'Delete test'],
            [ApiAction::DELETE_SUBRESOURCE, 'test', true, 'Delete test'],
            [ApiAction::GET_RELATIONSHIP, 'test', false, 'Get "test" relationship'],
            [ApiAction::GET_RELATIONSHIP, 'test', true, 'Get "test" relationship'],
            [ApiAction::UPDATE_RELATIONSHIP, 'test', false, 'Update "test" relationship'],
            [ApiAction::UPDATE_RELATIONSHIP, 'test', true, 'Replace "test" relationship'],
            [ApiAction::ADD_RELATIONSHIP, 'test', false, 'Add members to "test" relationship'],
            [ApiAction::ADD_RELATIONSHIP, 'test', true, 'Add members to "test" relationship'],
            [ApiAction::DELETE_RELATIONSHIP, 'test', false, 'Delete members from "test" relationship'],
            [ApiAction::DELETE_RELATIONSHIP, 'test', true, 'Delete members from "test" relationship']
        ];
    }

    /**
     * @dataProvider getSubresourceDocumentationProvider
     */
    public function testGetSubresourceDocumentation(
        string $action,
        string $associationDescription,
        bool $isCollection,
        ?string $expected
    ) {
        self::assertSame(
            $expected,
            $this->resourceDocProvider->getSubresourceDocumentation($action, $associationDescription, $isCollection)
        );
    }

    public function getSubresourceDocumentationProvider(): array
    {
        return [
            ['unknown', 'test', false, null],
            [ApiAction::OPTIONS, 'test', false, 'Get communication options for a resource'],
            [ApiAction::OPTIONS, 'test', true, 'Get communication options for a resource'],
            [ApiAction::GET_SUBRESOURCE, 'test', false, 'Get a related entity'],
            [ApiAction::GET_SUBRESOURCE, 'test', true, 'Get a list of related entities'],
            [ApiAction::UPDATE_SUBRESOURCE, 'test', false, 'Update the specified related entity'],
            [ApiAction::UPDATE_SUBRESOURCE, 'test', true, 'Update the specified related entities'],
            [ApiAction::ADD_SUBRESOURCE, 'test', false, 'Add the specified related entity'],
            [ApiAction::ADD_SUBRESOURCE, 'test', true, 'Add the specified related entities'],
            [ApiAction::DELETE_SUBRESOURCE, 'test', false, 'Delete the specified related entity'],
            [ApiAction::DELETE_SUBRESOURCE, 'test', true, 'Delete the specified related entities'],
            [ApiAction::GET_RELATIONSHIP, 'test', false, 'Get the relationship data'],
            [ApiAction::GET_RELATIONSHIP, 'test', true, 'Get the relationship data'],
            [ApiAction::UPDATE_RELATIONSHIP, 'test', false, 'Update the relationship'],
            [ApiAction::UPDATE_RELATIONSHIP, 'test', true, 'Completely replace every member of the relationship'],
            [ApiAction::ADD_RELATIONSHIP, 'test', false, 'Add the specified members to the relationship'],
            [ApiAction::ADD_RELATIONSHIP, 'test', true, 'Add the specified members to the relationship'],
            [ApiAction::DELETE_RELATIONSHIP, 'test', false, 'Delete the specified members from the relationship'],
            [ApiAction::DELETE_RELATIONSHIP, 'test', true, 'Delete the specified members from the relationship']
        ];
    }
}

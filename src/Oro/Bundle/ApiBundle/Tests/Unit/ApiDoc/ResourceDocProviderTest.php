<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;

class ResourceDocProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ResourceDocProvider */
    private $resourceDocProvider;

    protected function setUp()
    {
        $this->resourceDocProvider = new ResourceDocProvider();
    }

    /**
     * @dataProvider getResourceDescriptionProvider
     */
    public function testGetResourceDescription($action, $entityDescription, $expected)
    {
        self::assertSame(
            $expected,
            $this->resourceDocProvider->getResourceDescription($action, $entityDescription)
        );
    }

    public function getResourceDescriptionProvider()
    {
        return [
            ['unknown', 'Test', null],
            [ApiActions::OPTIONS, 'Test', 'Get options'],
            [ApiActions::GET, 'Test', 'Get Test'],
            [ApiActions::GET_LIST, 'Test', 'Get Test'],
            [ApiActions::UPDATE, 'Test', 'Update Test'],
            [ApiActions::CREATE, 'Test', 'Create Test'],
            [ApiActions::DELETE, 'Test', 'Delete Test'],
            [ApiActions::DELETE_LIST, 'Test', 'Delete Test']
        ];
    }

    /**
     * @dataProvider getResourceDocumentationProvider
     */
    public function testGetResourceDocumentation($action, $entityDescription, $expected)
    {
        self::assertSame(
            $expected,
            $this->resourceDocProvider->getResourceDocumentation($action, $entityDescription)
        );
    }

    public function getResourceDocumentationProvider()
    {
        return [
            ['unknown', 'Test', null],
            [ApiActions::OPTIONS, 'Test', 'Get communication options for a resource'],
            [ApiActions::GET, 'Test', 'Get an entity'],
            [ApiActions::GET_LIST, 'Test', 'Get a list of entities'],
            [ApiActions::UPDATE, 'Test', 'Update an entity'],
            [ApiActions::CREATE, 'Test', 'Create an entity'],
            [ApiActions::DELETE, 'Test', 'Delete an entity'],
            [ApiActions::DELETE_LIST, 'Test', 'Delete a list of entities']
        ];
    }

    /**
     * @dataProvider getSubresourceDescriptionProvider
     */
    public function testGetSubresourceDescription($action, $associationDescription, $isCollection, $expected)
    {
        self::assertSame(
            $expected,
            $this->resourceDocProvider->getSubresourceDescription($action, $associationDescription, $isCollection)
        );
    }

    public function getSubresourceDescriptionProvider()
    {
        return [
            ['unknown', 'Test', false, null],
            [ApiActions::OPTIONS, 'test', false, 'Get options'],
            [ApiActions::OPTIONS, 'test', true, 'Get options'],
            [ApiActions::GET_SUBRESOURCE, 'test', false, 'Get test'],
            [ApiActions::GET_SUBRESOURCE, 'test', true, 'Get test'],
            [ApiActions::UPDATE_SUBRESOURCE, 'test', false, 'Update test'],
            [ApiActions::UPDATE_SUBRESOURCE, 'test', true, 'Update test'],
            [ApiActions::ADD_SUBRESOURCE, 'test', false, 'Add test'],
            [ApiActions::ADD_SUBRESOURCE, 'test', true, 'Add test'],
            [ApiActions::DELETE_SUBRESOURCE, 'test', false, 'Delete test'],
            [ApiActions::DELETE_SUBRESOURCE, 'test', true, 'Delete test'],
            [ApiActions::GET_RELATIONSHIP, 'test', false, 'Get "test" relationship'],
            [ApiActions::GET_RELATIONSHIP, 'test', true, 'Get "test" relationship'],
            [ApiActions::UPDATE_RELATIONSHIP, 'test', false, 'Update "test" relationship'],
            [ApiActions::UPDATE_RELATIONSHIP, 'test', true, 'Replace "test" relationship'],
            [ApiActions::ADD_RELATIONSHIP, 'test', false, 'Add members to "test" relationship'],
            [ApiActions::ADD_RELATIONSHIP, 'test', true, 'Add members to "test" relationship'],
            [ApiActions::DELETE_RELATIONSHIP, 'test', false, 'Delete members from "test" relationship'],
            [ApiActions::DELETE_RELATIONSHIP, 'test', true, 'Delete members from "test" relationship']
        ];
    }

    /**
     * @dataProvider getSubresourceDocumentationProvider
     */
    public function testGetSubresourceDocumentation($action, $associationDescription, $isCollection, $expected)
    {
        self::assertSame(
            $expected,
            $this->resourceDocProvider->getSubresourceDocumentation($action, $associationDescription, $isCollection)
        );
    }

    public function getSubresourceDocumentationProvider()
    {
        return [
            ['unknown', 'Test', false, null],
            [ApiActions::OPTIONS, 'test', false, 'Get communication options for a resource'],
            [ApiActions::OPTIONS, 'test', true, 'Get communication options for a resource'],
            [ApiActions::GET_SUBRESOURCE, 'test', false, 'Get a related entity'],
            [ApiActions::GET_SUBRESOURCE, 'test', true, 'Get a list of related entities'],
            [ApiActions::UPDATE_SUBRESOURCE, 'test', false, 'Update the specified related entity'],
            [ApiActions::UPDATE_SUBRESOURCE, 'test', true, 'Update the specified related entities'],
            [ApiActions::ADD_SUBRESOURCE, 'test', false, 'Add the specified related entity'],
            [ApiActions::ADD_SUBRESOURCE, 'test', true, 'Add the specified related entities'],
            [ApiActions::DELETE_SUBRESOURCE, 'test', false, 'Delete the specified related entity'],
            [ApiActions::DELETE_SUBRESOURCE, 'test', true, 'Delete the specified related entities'],
            [ApiActions::GET_RELATIONSHIP, 'test', false, 'Get the relationship data'],
            [ApiActions::GET_RELATIONSHIP, 'test', true, 'Get the relationship data'],
            [ApiActions::UPDATE_RELATIONSHIP, 'test', false, 'Update the relationship'],
            [ApiActions::UPDATE_RELATIONSHIP, 'test', true, 'Completely replace every member of the relationship'],
            [ApiActions::ADD_RELATIONSHIP, 'test', false, 'Add the specified members to the relationship'],
            [ApiActions::ADD_RELATIONSHIP, 'test', true, 'Add the specified members to the relationship'],
            [ApiActions::DELETE_RELATIONSHIP, 'test', false, 'Delete the specified members from the relationship'],
            [ApiActions::DELETE_RELATIONSHIP, 'test', true, 'Delete the specified members from the relationship']
        ];
    }
}

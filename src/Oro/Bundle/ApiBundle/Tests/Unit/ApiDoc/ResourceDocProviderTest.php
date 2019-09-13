<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;

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
            [ApiAction::OPTIONS, 'Test', 'Get options'],
            [ApiAction::GET, 'Test', 'Get Test'],
            [ApiAction::GET_LIST, 'Test', 'Get Test'],
            [ApiAction::UPDATE, 'Test', 'Update Test'],
            [ApiAction::CREATE, 'Test', 'Create Test'],
            [ApiAction::DELETE, 'Test', 'Delete Test'],
            [ApiAction::DELETE_LIST, 'Test', 'Delete Test']
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
            [ApiAction::OPTIONS, 'Test', 'Get communication options for a resource'],
            [ApiAction::GET, 'Test', 'Get an entity'],
            [ApiAction::GET_LIST, 'Test', 'Get a list of entities'],
            [ApiAction::UPDATE, 'Test', 'Update an entity'],
            [ApiAction::CREATE, 'Test', 'Create an entity'],
            [ApiAction::DELETE, 'Test', 'Delete an entity'],
            [ApiAction::DELETE_LIST, 'Test', 'Delete a list of entities']
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

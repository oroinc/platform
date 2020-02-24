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
            ['unknown', 'Product', null],
            [ApiAction::OPTIONS, 'Product', 'Get options'],
            [ApiAction::GET, 'Product', 'Get Product'],
            [ApiAction::GET_LIST, 'Products', 'Get Products'],
            [ApiAction::UPDATE, 'Product', 'Update Product'],
            [ApiAction::CREATE, 'Product', 'Create Product'],
            [ApiAction::DELETE, 'Product', 'Delete Product'],
            [ApiAction::DELETE_LIST, 'Products', 'Delete Products']
        ];
    }

    /**
     * @dataProvider getResourceDocumentationProvider
     */
    public function testGetResourceDocumentation($action, $entitySingularName, $entityPluralName, $expected)
    {
        self::assertSame(
            $expected,
            $this->resourceDocProvider->getResourceDocumentation($action, $entitySingularName, $entityPluralName)
        );
    }

    public function getResourceDocumentationProvider()
    {
        return [
            ['unknown', 'Product', 'Products', null],
            [ApiAction::OPTIONS, 'Product', 'Products', 'Get communication options for a resource'],
            [ApiAction::GET, 'Product', 'Products', 'Get an entity'],
            [ApiAction::GET_LIST, 'Product', 'Products', 'Get a list of entities'],
            [ApiAction::UPDATE, 'Product', 'Products', 'Update an entity'],
            [ApiAction::CREATE, 'Product', 'Products', 'Create an entity'],
            [ApiAction::DELETE, 'Product', 'Products', 'Delete an entity'],
            [ApiAction::DELETE_LIST, 'Product', 'Products', 'Delete a list of entities']
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

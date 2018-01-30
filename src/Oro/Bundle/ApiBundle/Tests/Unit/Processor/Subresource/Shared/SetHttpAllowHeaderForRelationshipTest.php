<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\SetHttpAllowHeaderForRelationship;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;

class SetHttpAllowHeaderForRelationshipTest extends ChangeRelationshipProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ResourcesProvider */
    private $resourcesProvider;

    /** @var SetHttpAllowHeaderForRelationship */
    private $processor;

    public function setUp()
    {
        parent::setUp();

        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->processor = new SetHttpAllowHeaderForRelationship($this->resourcesProvider);
    }

    public function testProcessWhenResponseStatusCodeIsNot405()
    {
        $this->resourcesProvider->expects(self::never())
            ->method('getResourceExcludeActions');

        $this->context->setResponseStatusCode(404);
        $this->context->setParentClassName('Test\Class');
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }

    public function testProcessWhenAllowResponseHeaderAlreadySet()
    {
        $this->resourcesProvider->expects(self::never())
            ->method('getResourceExcludeActions');

        $this->context->setResponseStatusCode(405);
        $this->context->getResponseHeaders()->set('Allow', 'GET');
        $this->context->setParentClassName('Test\Class');
        $this->processor->process($this->context);

        self::assertEquals('GET', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessToOneAssociationWhenAtLeastOneAllowedHttpMethodExists()
    {
        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([ApiActions::UPDATE_RELATIONSHIP]);

        $this->context->setResponseStatusCode(405);
        $this->context->setParentClassName('Test\Class');
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        self::assertEquals('GET', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessToManyAssociationWhenAtLeastOneAllowedHttpMethodExists()
    {
        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([ApiActions::UPDATE_RELATIONSHIP]);

        $this->context->setResponseStatusCode(405);
        $this->context->setParentClassName('Test\Class');
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        self::assertEquals('GET, POST, DELETE', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessToOneAssociationWhenNoAllowedHttpMethods()
    {
        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([ApiActions::GET_RELATIONSHIP, ApiActions::UPDATE_RELATIONSHIP]);

        $this->context->setResponseStatusCode(405);
        $this->context->setParentClassName('Test\Class');
        $this->context->setIsCollection(false);
        $this->processor->process($this->context);

        self::assertEquals(404, $this->context->getResponseStatusCode());
        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }

    public function testProcessToManyAssociationWhenNoAllowedHttpMethods()
    {
        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([
                ApiActions::GET_RELATIONSHIP,
                ApiActions::UPDATE_RELATIONSHIP,
                ApiActions::ADD_RELATIONSHIP,
                ApiActions::DELETE_RELATIONSHIP
            ]);

        $this->context->setResponseStatusCode(405);
        $this->context->setParentClassName('Test\Class');
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        self::assertEquals(404, $this->context->getResponseStatusCode());
        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\SetHttpAllowHeaderForList;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class SetHttpAllowHeaderForListTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourcesProvider */
    private $resourcesProvider;

    /** @var SetHttpAllowHeaderForList */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->processor = new SetHttpAllowHeaderForList($this->resourcesProvider);
    }

    public function testProcessWhenResponseStatusCodeIsNot405()
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->resourcesProvider->expects(self::never())
            ->method('getResourceExcludeActions');

        $this->context->setResponseStatusCode(404);
        $this->context->setClassName('Test\Class');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }

    public function testProcessWhenAllowResponseHeaderAlreadySet()
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->resourcesProvider->expects(self::never())
            ->method('getResourceExcludeActions');

        $this->context->setResponseStatusCode(405);
        $this->context->getResponseHeaders()->set('Allow', 'GET');
        $this->context->setClassName('Test\Class');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals('GET', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessWhenAtLeastOneAllowedHttpMethodExists()
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([ApiAction::DELETE_LIST]);

        $this->context->setResponseStatusCode(405);
        $this->context->setClassName('Test\Class');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals('OPTIONS, GET, PATCH, POST', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessWhenNoAllowedHttpMethods()
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([
                ApiAction::GET_LIST,
                ApiAction::UPDATE_LIST,
                ApiAction::DELETE_LIST,
                ApiAction::CREATE
            ]);

        $this->context->setResponseStatusCode(405);
        $this->context->setClassName('Test\Class');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals(404, $this->context->getResponseStatusCode());
        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }

    public function testProcessWhenEntityDoesNotHaveIdentifierFields()
    {
        $metadata = new EntityMetadata('Test\Entity');

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);

        $this->context->setResponseStatusCode(405);
        $this->context->setClassName('Test\Class');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals('OPTIONS, GET, PATCH, POST, DELETE', $this->context->getResponseHeaders()->get('Allow'));
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\SetHttpAllowHeaderForSingleItem;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class SetHttpAllowHeaderForSingleItemTest extends GetProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ResourcesProvider */
    private $resourcesProvider;

    /** @var SetHttpAllowHeaderForSingleItem */
    private $processor;

    public function setUp()
    {
        parent::setUp();

        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->processor = new SetHttpAllowHeaderForSingleItem($this->resourcesProvider);
    }

    public function testProcessWhenResponseStatusCodeIsNot405()
    {
        $this->resourcesProvider->expects(self::never())
            ->method('getResourceExcludeActions');

        $this->context->setResponseStatusCode(404);
        $this->context->setClassName('Test\Class');
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }

    public function testProcessWhenAllowResponseHeaderAlreadySet()
    {
        $this->resourcesProvider->expects(self::never())
            ->method('getResourceExcludeActions');

        $this->context->setResponseStatusCode(405);
        $this->context->getResponseHeaders()->set('Allow', 'GET');
        $this->context->setClassName('Test\Class');
        $this->processor->process($this->context);

        self::assertEquals('GET', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessWhenAtLeastOneAllowedHttpMethodExists()
    {
        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([ApiActions::CREATE, ApiActions::DELETE]);

        $this->context->setResponseStatusCode(405);
        $this->context->setClassName('Test\Class');
        $this->processor->process($this->context);

        self::assertEquals('GET, PATCH', $this->context->getResponseHeaders()->get('Allow'));
    }

    public function testProcessWhenNoAllowedHttpMethods()
    {
        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([ApiActions::GET, ApiActions::CREATE, ApiActions::UPDATE, ApiActions::DELETE]);

        $this->context->setResponseStatusCode(405);
        $this->context->setClassName('Test\Class');
        $this->processor->process($this->context);

        self::assertEquals(404, $this->context->getResponseStatusCode());
        self::assertFalse($this->context->getResponseHeaders()->has('Allow'));
    }
}

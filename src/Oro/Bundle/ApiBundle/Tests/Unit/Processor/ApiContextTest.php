<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use PHPUnit\Framework\TestCase;

class ApiContextTest extends TestCase
{
    private ApiContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new ApiContextStub();
    }

    public function testRequestType(): void
    {
        self::assertEquals(new RequestType([]), $this->context->getRequestType());

        $this->context->getRequestType()->add('test');
        self::assertEquals(new RequestType(['test']), $this->context->getRequestType());
        self::assertEquals(new RequestType(['test']), $this->context->get(ApiContext::REQUEST_TYPE));

        $this->context->getRequestType()->add('another');
        self::assertEquals(new RequestType(['test', 'another']), $this->context->getRequestType());
        self::assertEquals(new RequestType(['test', 'another']), $this->context->get(ApiContext::REQUEST_TYPE));

        // test that already existing type is not added twice
        $this->context->getRequestType()->add('another');
        self::assertEquals(new RequestType(['test', 'another']), $this->context->getRequestType());
        self::assertEquals(new RequestType(['test', 'another']), $this->context->get(ApiContext::REQUEST_TYPE));
    }

    public function testVersion(): void
    {
        self::assertSame('', $this->context->getVersion());

        $this->context->setVersion('test');
        self::assertEquals('test', $this->context->getVersion());
        self::assertEquals('test', $this->context->get(ApiContext::VERSION));
    }

    public function testProcessed(): void
    {
        self::assertFalse($this->context->isProcessed('test'));

        $this->context->setProcessed('test');
        $this->context->setProcessed('another');
        self::assertTrue($this->context->isProcessed('test'));
        self::assertTrue($this->context->isProcessed('another'));

        $this->context->clearProcessed('test');
        self::assertFalse($this->context->isProcessed('test'));
        self::assertTrue($this->context->isProcessed('another'));
    }
}

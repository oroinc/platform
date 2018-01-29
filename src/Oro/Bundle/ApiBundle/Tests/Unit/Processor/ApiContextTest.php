<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ApiContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var ApiContext */
    protected $context;

    protected function setUp()
    {
        $this->context = new ApiContextStub();
    }

    public function testRequestType()
    {
        $this->assertEquals(new RequestType([]), $this->context->getRequestType());

        $this->context->getRequestType()->add('test');
        $this->assertEquals(new RequestType(['test']), $this->context->getRequestType());
        $this->assertEquals(new RequestType(['test']), $this->context->get(ApiContext::REQUEST_TYPE));

        $this->context->getRequestType()->add('another');
        $this->assertEquals(new RequestType(['test', 'another']), $this->context->getRequestType());
        $this->assertEquals(new RequestType(['test', 'another']), $this->context->get(ApiContext::REQUEST_TYPE));

        // test that already existing type is not added twice
        $this->context->getRequestType()->add('another');
        $this->assertEquals(new RequestType(['test', 'another']), $this->context->getRequestType());
        $this->assertEquals(new RequestType(['test', 'another']), $this->context->get(ApiContext::REQUEST_TYPE));
    }

    public function testVersion()
    {
        $this->assertNull($this->context->getVersion());

        $this->context->setVersion('test');
        $this->assertEquals('test', $this->context->getVersion());
        $this->assertEquals('test', $this->context->get(ApiContext::VERSION));
    }

    public function testProcessed()
    {
        $this->assertFalse($this->context->isProcessed('test'));

        $this->context->setProcessed('test');
        $this->context->setProcessed('another');
        $this->assertTrue($this->context->isProcessed('test'));
        $this->assertTrue($this->context->isProcessed('another'));

        $this->context->clearProcessed('test');
        $this->assertFalse($this->context->isProcessed('test'));
        $this->assertTrue($this->context->isProcessed('another'));
    }
}

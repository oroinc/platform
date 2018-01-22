<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

class NormalizeValueContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var NormalizeValueContext */
    protected $context;

    public function setUp()
    {
        $this->context = new NormalizeValueContext();
    }

    public function testRequestType()
    {
        $this->assertEquals(new RequestType([]), $this->context->getRequestType());

        $this->context->getRequestType()->add('test');
        $this->assertEquals(new RequestType(['test']), $this->context->getRequestType());
        $this->assertEquals(
            new RequestType(['test']),
            $this->context->get(NormalizeValueContext::REQUEST_TYPE)
        );

        $this->context->getRequestType()->add('another');
        $this->assertEquals(new RequestType(['test', 'another']), $this->context->getRequestType());
        $this->assertEquals(
            new RequestType(['test', 'another']),
            $this->context->get(NormalizeValueContext::REQUEST_TYPE)
        );

        // test that already existing type is not added twice
        $this->context->getRequestType()->add('another');
        $this->assertEquals(new RequestType(['test', 'another']), $this->context->getRequestType());
        $this->assertEquals(
            new RequestType(['test', 'another']),
            $this->context->get(NormalizeValueContext::REQUEST_TYPE)
        );
    }

    public function testVersion()
    {
        $this->assertNull($this->context->getVersion());

        $this->context->setVersion('test');
        $this->assertEquals('test', $this->context->getVersion());
        $this->assertEquals('test', $this->context->get(NormalizeValueContext::VERSION));
    }

    public function testProcessed()
    {
        $this->assertFalse($this->context->isProcessed());
        $this->context->setProcessed(true);
        $this->assertTrue($this->context->isProcessed());
    }

    public function testArrayDelimiter()
    {
        $this->assertEquals(',', $this->context->getArrayDelimiter());
        $this->context->setArrayDelimiter('-');
        $this->assertEquals('-', $this->context->getArrayDelimiter());
    }

    public function testRangeDelimiter()
    {
        $this->assertEquals('..', $this->context->getRangeDelimiter());
        $this->context->setRangeDelimiter('|');
        $this->assertEquals('|', $this->context->getRangeDelimiter());
    }

    public function testDataType()
    {
        $this->assertFalse($this->context->has('dataType'));
        $this->context->setDataType('string');
        $this->assertEquals('string', $this->context->getDataType());
        $this->assertEquals('string', $this->context->get('dataType'));
    }

    public function testRequirement()
    {
        $this->assertFalse($this->context->has('requirement'));
        $this->context->setRequirement('.+');
        $this->assertEquals('.+', $this->context->getRequirement());
        $this->assertEquals('.+', $this->context->get('requirement'));
        $this->context->removeRequirement();
        $this->assertFalse($this->context->has('requirement'));
    }

    public function testArrayAllowed()
    {
        $this->assertFalse($this->context->has('arrayAllowed'));
        $this->context->setArrayAllowed(true);
        $this->assertTrue($this->context->isArrayAllowed());
        $this->assertTrue($this->context->get('arrayAllowed'));
    }

    public function testRangeAllowed()
    {
        $this->assertFalse($this->context->has('rangeAllowed'));
        $this->context->setRangeAllowed(true);
        $this->assertTrue($this->context->isRangeAllowed());
        $this->assertTrue($this->context->get('rangeAllowed'));
    }
}

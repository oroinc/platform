<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

class NormalizeValueContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var NormalizeValueContext */
    private $context;

    protected function setUp()
    {
        $this->context = new NormalizeValueContext();
    }

    public function testRequestType()
    {
        self::assertEquals(new RequestType([]), $this->context->getRequestType());

        $this->context->getRequestType()->add('test');
        self::assertEquals(new RequestType(['test']), $this->context->getRequestType());
        self::assertEquals(
            new RequestType(['test']),
            $this->context->get(NormalizeValueContext::REQUEST_TYPE)
        );

        $this->context->getRequestType()->add('another');
        self::assertEquals(new RequestType(['test', 'another']), $this->context->getRequestType());
        self::assertEquals(
            new RequestType(['test', 'another']),
            $this->context->get(NormalizeValueContext::REQUEST_TYPE)
        );

        // test that already existing type is not added twice
        $this->context->getRequestType()->add('another');
        self::assertEquals(new RequestType(['test', 'another']), $this->context->getRequestType());
        self::assertEquals(
            new RequestType(['test', 'another']),
            $this->context->get(NormalizeValueContext::REQUEST_TYPE)
        );
    }

    public function testVersion()
    {
        self::assertNull($this->context->getVersion());

        $this->context->setVersion('test');
        self::assertEquals('test', $this->context->getVersion());
        self::assertEquals('test', $this->context->get(NormalizeValueContext::VERSION));
    }

    public function testProcessed()
    {
        self::assertFalse($this->context->isProcessed());
        $this->context->setProcessed(true);
        self::assertTrue($this->context->isProcessed());
    }

    public function testArrayDelimiter()
    {
        self::assertEquals(',', $this->context->getArrayDelimiter());
        $this->context->setArrayDelimiter('-');
        self::assertEquals('-', $this->context->getArrayDelimiter());
    }

    public function testRangeDelimiter()
    {
        self::assertEquals('..', $this->context->getRangeDelimiter());
        $this->context->setRangeDelimiter('|');
        self::assertEquals('|', $this->context->getRangeDelimiter());
    }

    public function testDataType()
    {
        self::assertFalse($this->context->has('dataType'));
        $this->context->setDataType('string');
        self::assertEquals('string', $this->context->getDataType());
        self::assertEquals('string', $this->context->get('dataType'));
    }

    public function testRequirement()
    {
        self::assertFalse($this->context->has('requirement'));
        $this->context->setRequirement('.+');
        self::assertEquals('.+', $this->context->getRequirement());
        self::assertEquals('.+', $this->context->get('requirement'));
        $this->context->removeRequirement();
        self::assertFalse($this->context->has('requirement'));
    }

    public function testArrayAllowed()
    {
        self::assertFalse($this->context->has('arrayAllowed'));
        $this->context->setArrayAllowed(true);
        self::assertTrue($this->context->isArrayAllowed());
        self::assertTrue($this->context->get('arrayAllowed'));
    }

    public function testRangeAllowed()
    {
        self::assertFalse($this->context->has('rangeAllowed'));
        $this->context->setRangeAllowed(true);
        self::assertTrue($this->context->isRangeAllowed());
        self::assertTrue($this->context->get('rangeAllowed'));
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NormalizeValueContextTest extends \PHPUnit\Framework\TestCase
{
    private NormalizeValueContext $context;

    protected function setUp(): void
    {
        $this->context = new NormalizeValueContext();
    }

    public function testRequestType()
    {
        self::assertTrue($this->context->has('requestType'));
        self::assertEquals(new RequestType([]), $this->context->getRequestType());

        $this->context->getRequestType()->add('test');
        self::assertEquals(new RequestType(['test']), $this->context->getRequestType());
        self::assertEquals(new RequestType(['test']), $this->context->get('requestType'));

        $this->context->getRequestType()->add('another');
        self::assertEquals(new RequestType(['test', 'another']), $this->context->getRequestType());
        self::assertEquals(new RequestType(['test', 'another']), $this->context->get('requestType'));

        // test that already existing type is not added twice
        $this->context->getRequestType()->add('another');
        self::assertEquals(new RequestType(['test', 'another']), $this->context->getRequestType());
        self::assertEquals(new RequestType(['test', 'another']), $this->context->get('requestType'));
    }

    public function testVersionWhenItIsNotSet()
    {
        $this->expectException(\TypeError::class);
        $this->context->getVersion();
    }

    public function testVersion()
    {
        $this->context->setVersion('test');
        self::assertEquals('test', $this->context->getVersion());
        self::assertEquals('test', $this->context->get('version'));
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

    public function testDataTypeWhenItIsNotSet()
    {
        $this->expectException(\TypeError::class);
        $this->context->getDataType();
    }

    public function testDataType()
    {
        $this->context->setDataType('string');
        self::assertEquals('string', $this->context->getDataType());
        self::assertEquals('string', $this->context->get('dataType'));
    }

    public function testRequirement()
    {
        self::assertFalse($this->context->hasRequirement());
        self::assertNull($this->context->getRequirement());

        $this->context->setRequirement('.+');
        self::assertTrue($this->context->hasRequirement());
        self::assertEquals('.+', $this->context->getRequirement());

        $this->context->removeRequirement();
        self::assertFalse($this->context->hasRequirement());
        self::assertNull($this->context->getRequirement());
    }

    public function testArrayAllowed()
    {
        self::assertFalse($this->context->isArrayAllowed());

        $this->context->setArrayAllowed(true);
        self::assertTrue($this->context->isArrayAllowed());
    }

    public function testRangeAllowed()
    {
        self::assertFalse($this->context->isRangeAllowed());

        $this->context->setRangeAllowed(true);
        self::assertTrue($this->context->isRangeAllowed());
    }
}

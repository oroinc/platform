<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NormalizeValueContextTest extends TestCase
{
    private NormalizeValueContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new NormalizeValueContext();
    }

    public function testRequestType(): void
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

    public function testVersionWhenItIsNotSet(): void
    {
        $this->expectException(\TypeError::class);
        $this->context->getVersion();
    }

    public function testVersion(): void
    {
        $this->context->setVersion('test');
        self::assertEquals('test', $this->context->getVersion());
        self::assertEquals('test', $this->context->get('version'));
    }

    public function testProcessed(): void
    {
        self::assertFalse($this->context->isProcessed());

        $this->context->setProcessed(true);
        self::assertTrue($this->context->isProcessed());
    }

    public function testArrayDelimiter(): void
    {
        self::assertEquals(',', $this->context->getArrayDelimiter());

        $this->context->setArrayDelimiter('-');
        self::assertEquals('-', $this->context->getArrayDelimiter());
    }

    public function testRangeDelimiter(): void
    {
        self::assertEquals('..', $this->context->getRangeDelimiter());

        $this->context->setRangeDelimiter('|');
        self::assertEquals('|', $this->context->getRangeDelimiter());
    }

    public function testDataTypeWhenItIsNotSet(): void
    {
        $this->expectException(\TypeError::class);
        $this->context->getDataType();
    }

    public function testDataType(): void
    {
        $this->context->setDataType('string');
        self::assertEquals('string', $this->context->getDataType());
        self::assertEquals('string', $this->context->get('dataType'));
    }

    public function testRequirement(): void
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

    public function testArrayAllowed(): void
    {
        self::assertFalse($this->context->isArrayAllowed());

        $this->context->setArrayAllowed(true);
        self::assertTrue($this->context->isArrayAllowed());
    }

    public function testRangeAllowed(): void
    {
        self::assertFalse($this->context->isRangeAllowed());

        $this->context->setRangeAllowed(true);
        self::assertTrue($this->context->isRangeAllowed());
    }

    public function testOptions(): void
    {
        self::assertSame([], $this->context->getOptions());

        $this->context->addOption('option1', true);
        self::assertSame(['option1' => true], $this->context->getOptions());

        $this->context->addOption('option2', 'val');
        self::assertSame(['option1' => true, 'option2' => 'val'], $this->context->getOptions());

        $this->context->removeOption('option1');
        self::assertSame(['option2' => 'val'], $this->context->getOptions());

        $this->context->removeOption('option2');
        self::assertSame([], $this->context->getOptions());
    }
}

<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\DataProviderDecorator;
use Oro\Component\Layout\Tests\Unit\Fixtures\DataProviderStub;
use PHPUnit\Framework\TestCase;

class DataProviderDecoratorTest extends TestCase
{
    private DataProviderDecorator $decorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->decorator = new DataProviderDecorator(new DataProviderStub(['key1' => 'value1']));
    }

    public function testCallForGetMethod(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method "get" cannot be called. The method name should start with "get", "has" or "is".'
        );

        $this->decorator->get('key1');
    }

    public function testCallForMethodWithGetPrefix(): void
    {
        $this->assertEquals('value1', $this->decorator->getValue('key1'));
    }

    public function testCallForHasMethod(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method "has" cannot be called. The method name should start with "get", "has" or "is".'
        );

        $this->decorator->has('key1');
    }

    public function testCallForMethodWithHasPrefix(): void
    {
        $this->assertTrue($this->decorator->hasValue('key1'));
    }

    public function testCallForIsMethod(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method "is" cannot be called. The method name should start with "get", "has" or "is".'
        );

        $this->decorator->is('key1');
    }

    public function testCallForMethodWithIsPrefix(): void
    {
        $this->assertTrue($this->decorator->isValue('key1'));
    }

    public function testCallForNotAllowedMethod(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method "set" cannot be called. The method name should start with "get", "has" or "is".'
        );

        $this->decorator->set('key2', 'value2');
    }

    public function testCallForMethodWithNotAllowedPrefix(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method "setValue" cannot be called. The method name should start with "get", "has" or "is".'
        );

        $this->decorator->setValue('key2', 'value2');
    }

    public function testCallForNotExistingMethod(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage(sprintf('Call to undefined method %s::getAnother()', DataProviderStub::class));

        $this->decorator->getAnother();
    }

    public function testCallForMethodWithoutArguments(): void
    {
        $this->assertSame(1, $this->decorator->getCount());
    }
}

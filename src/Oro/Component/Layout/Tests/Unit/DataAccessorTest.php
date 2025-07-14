<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\DataAccessor;
use Oro\Component\Layout\DataProviderDecorator;
use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutRegistryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DataAccessorTest extends TestCase
{
    private LayoutRegistryInterface&MockObject $registry;
    private LayoutContext $context;
    private DataAccessor $dataAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(LayoutRegistryInterface::class);
        $this->context = new LayoutContext();

        $this->dataAccessor = new DataAccessor(
            $this->registry,
            $this->context
        );
    }

    public function testGet(): void
    {
        $name = 'foo';
        $expectedData = new \stdClass();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn($expectedData);

        $this->assertEquals(new DataProviderDecorator($expectedData), $this->dataAccessor->get($name));
    }

    public function testArrayAccessGet(): void
    {
        $name = 'foo';
        $expectedData = new \stdClass();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn($expectedData);

        $this->assertEquals(new DataProviderDecorator($expectedData), $this->dataAccessor[$name]);
    }

    public function testGetFromContextData(): void
    {
        $name = 'foo';
        $expectedData = new \stdClass();

        $this->context[$name] = 'other';
        $this->context->getResolver()->setDefined([$name]);
        $this->context->data()->set($name, $expectedData);
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn(null);

        $this->assertSame($expectedData, $this->dataAccessor->get($name));
    }

    public function testArrayAccessGetFromContextData(): void
    {
        $name = 'foo';
        $expectedData = new \stdClass();

        $this->context[$name] = 'other';
        $this->context->getResolver()->setDefined([$name]);
        $this->context->data()->set($name, $expectedData);
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn(null);

        $this->assertSame($expectedData, $this->dataAccessor[$name]);
    }

    public function testGetFromContextThrowsExceptionIfContextDataDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not load the data provider "foo".');

        $name = 'foo';
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn(null);

        $this->dataAccessor->get($name);
    }

    public function testArrayAccessGetFromContextThrowsExceptionIfContextDataDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not load the data provider "foo".');

        $name = 'foo';
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn(null);

        $this->dataAccessor[$name];
    }

    public function testArrayAccessExistsForUnknownDataProvider(): void
    {
        $name = 'foo';
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn(null);

        $this->assertFalse(isset($this->dataAccessor[$name]));
    }

    public function testArrayAccessExists(): void
    {
        $name = 'foo';
        $expectedData = new \stdClass();

        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn($expectedData);

        $this->assertTrue(isset($this->dataAccessor[$name]));

        $this->assertEquals(new DataProviderDecorator($expectedData), $this->dataAccessor[$name]);
    }

    public function testArrayAccessExistsForContextData(): void
    {
        $name = 'foo';
        $this->context->data()->set($name, 'val');
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn(null);

        $this->assertTrue(isset($this->dataAccessor[$name]));
    }

    public function testArrayAccessSetThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not supported');

        $this->dataAccessor['foo'] = 'bar';
    }

    public function testArrayAccessRemoveThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not supported');

        unset($this->dataAccessor['foo']);
    }
}

<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\DataAccessor;
use Oro\Component\Layout\DataProviderDecorator;
use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutRegistryInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DataAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var LayoutContext */
    private $context;

    /** @var DataAccessor */
    private $dataAccessor;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(LayoutRegistryInterface::class);
        $this->context = new LayoutContext();

        $this->dataAccessor = new DataAccessor(
            $this->registry,
            $this->context
        );
    }

    public function testGet()
    {
        $name = 'foo';
        $expectedData = new \stdClass();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn($expectedData);

        $this->assertEquals(new DataProviderDecorator($expectedData), $this->dataAccessor->get($name));
    }

    public function testArrayAccessGet()
    {
        $name = 'foo';
        $expectedData = new \stdClass();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn($expectedData);

        $this->assertEquals(new DataProviderDecorator($expectedData), $this->dataAccessor[$name]);
    }

    public function testGetFromContextData()
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

    public function testArrayAccessGetFromContextData()
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

    public function testGetFromContextThrowsExceptionIfContextDataDoesNotExist()
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

    public function testArrayAccessGetFromContextThrowsExceptionIfContextDataDoesNotExist()
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

    public function testArrayAccessExistsForUnknownDataProvider()
    {
        $name = 'foo';
        $this->context->resolve();

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->willReturn(null);

        $this->assertFalse(isset($this->dataAccessor[$name]));
    }

    public function testArrayAccessExists()
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

    public function testArrayAccessExistsForContextData()
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

    public function testArrayAccessSetThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not supported');

        $this->dataAccessor['foo'] = 'bar';
    }

    public function testArrayAccessRemoveThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not supported');

        unset($this->dataAccessor['foo']);
    }
}

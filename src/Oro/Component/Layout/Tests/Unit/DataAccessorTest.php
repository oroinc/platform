<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\DataProviderRegistry;
use Oro\Component\Layout\LayoutContext;

class DataProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var LayoutContext */
    protected $context;

    /** @var DataProviderRegistry */
    protected $dataProviderRegistry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Oro\Component\Layout\LayoutRegistryInterface');
        $this->context  = new LayoutContext();

        $this->dataProviderRegistry = new DataProviderRegistry(
            $this->registry,
            $this->context
        );
    }

    public function testGetFromRegistry()
    {
        $name         = 'foo';
        $dataProvider = $this->getMock('Oro\Component\Layout\DataProviderInterface');

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue($dataProvider));

        $this->assertSame($dataProvider, $this->dataProviderRegistry->get($name));
    }

    public function testArrayAccessGetFromRegistry()
    {
        $name         = 'foo';
        $dataProvider = $this->getMock('Oro\Component\Layout\DataProviderInterface');

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue($dataProvider));

        $this->assertSame($dataProvider, $this->dataProviderRegistry[$name]);
    }

    public function testGetFromContext()
    {
        $name                 = 'foo';
        $data                 = new \stdClass();
        $this->context[$name] = $data;

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue(null));

        $dataProvider = $this->dataProviderRegistry->get($name);
        $this->assertInstanceOf(
            'Oro\Component\Layout\ContextAwareDataProvider',
            $dataProvider
        );
        $this->assertSame($data, $dataProvider->getData());

        // test that context aware data provider is cached
        $this->dataProviderRegistry->get($name);
    }

    public function testArrayAccessGetFromContext()
    {
        $name                 = 'foo';
        $data                 = new \stdClass();
        $this->context[$name] = $data;

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue(null));

        $dataProvider = $this->dataProviderRegistry[$name];
        $this->assertInstanceOf(
            'Oro\Component\Layout\ContextAwareDataProvider',
            $dataProvider
        );
        $this->assertSame($data, $dataProvider->getData());

        // test that context aware data provider is cached
        $this->dataProviderRegistry[$name];
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not load a data provider "foo".
     */
    public function testGetFromContextThrowsExceptionIfContextVariableDoesNotExist()
    {
        $name = 'foo';

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue(null));

        $this->dataProviderRegistry->get($name);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not load a data provider "foo".
     */
    public function testArrayAccessGetFromContextThrowsExceptionIfContextVariableDoesNotExist()
    {
        $name = 'foo';

        $this->registry->expects($this->once())
            ->method('findDataProvider')
            ->with($name)
            ->will($this->returnValue(null));

        $this->dataProviderRegistry[$name];
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testArrayAccessExistsThrowsException()
    {
        $result = isset($this->dataProviderRegistry['foo']);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testArrayAccessSetThrowsException()
    {
        $this->dataProviderRegistry['foo'] = 'bar';
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testArrayAccessRemoveThrowsException()
    {
        unset($this->dataProviderRegistry['foo']);
    }
}

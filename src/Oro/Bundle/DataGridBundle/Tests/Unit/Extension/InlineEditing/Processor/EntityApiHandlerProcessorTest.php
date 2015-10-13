<?php

namespace Oro\Bundle\DatagridBundle\Tests\Unit\Extension\InlineEditing\Processor;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Processor\EntityApiHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Processor\EntityApiHandlerProcessor;
use Oro\Bundle\DatagridBundle\Tests\Unit\Stub\SomeEntity;

class EntityApiHandlerProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityApiHandlerProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->processor = new EntityApiHandlerProcessor();
    }

    public function testAddAndGetHandlers()
    {
        $handlers = [$this->getHandlerMock(), $this->getHandlerMock()];

        foreach ($handlers as $handler) {
            $this->processor->addHandler($handler);
        }

        $this->assertEquals(
            $handlers,
            $this->processor->getHandlers()
        );
    }

    public function testGetHandlerByClass()
    {
        $entity = new SomeEntity();
        $handler = $this->getHandlerMock();
        $this->processor->addHandler($handler);

        $handler
            ->expects($this->once())
            ->method('getClass')
            ->willReturn(ClassUtils::getClass($entity));

        $this->assertEquals(
            $handler,
            $this->processor->getHandlerByClass(ClassUtils::getClass($entity))
        );
    }

    public function testGetHandlerByClassNull()
    {
        $entity = new SomeEntity();
        $handler = $this->getHandlerMock();
        $this->processor->addHandler($handler);

        $handler
            ->expects($this->once())
            ->method('getClass')
            ->willReturn(ClassUtils::getClass($entity));

        $this->assertEquals(
            null,
            $this->processor->getHandlerByClass('test/class')
        );
    }

    public function testPreProcess()
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'preProcess', 1);

        $this->processor->preProcess($entity);
    }

    public function testPreProcessSkipp()
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'preProcess');

        $this->processor->preProcess($entity);
    }

    public function testBeforeProcess()
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'beforeProcess', 1);

        $this->processor->beforeProcess($entity);
    }

    public function testBeforeProcessSkipp()
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'beforeProcess');

        $this->processor->beforeProcess($entity);
    }

    public function testAfterProcess()
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'afterProcess', 1);

        $this->processor->afterProcess($entity);
    }

    public function testAfterProcessSkipp()
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'afterProcess');

        $this->processor->invalidateProcess($entity);
    }

    public function testInvalidateProcess()
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'invalidateProcess', 1);

        $this->processor->invalidateProcess($entity);
    }

    public function testInvalidateProcessSkipp()
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'invalidateProcess');

        $this->processor->invalidateProcess($entity);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityApiHandlerInterface
     */
    protected function getHandlerMock()
    {
        return $this->getMock('Oro\Bundle\DataGridBundle\Extension\InlineEditing\Processor\EntityApiHandlerInterface');
    }

    /**
     * @param $entity
     * @param string $method
     * @param int $count
     */
    protected function prepareProcess($entity, $method, $count = 0)
    {
        $handler = $this->getHandlerMock();
        $this->processor->addHandler($handler);

        if ($count === 0) {
            $handler
                ->expects($this->once())
                ->method('getClass')
                ->willReturn('some/class');
        } else {
            $handler
                ->expects($this->once())
                ->method('getClass')
                ->willReturn(ClassUtils::getClass($entity));
        }

        $handler
            ->expects($this->exactly($count))
            ->method($method);
    }
}

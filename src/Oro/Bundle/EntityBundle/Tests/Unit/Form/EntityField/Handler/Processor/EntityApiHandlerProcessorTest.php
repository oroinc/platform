<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\EntityField\Handler\Processor;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor\EntityApiHandlerInterface;
use Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor\EntityApiHandlerProcessor;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub\SomeEntity;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityApiHandlerProcessorTest extends TestCase
{
    private EntityApiHandlerProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->processor = new EntityApiHandlerProcessor();
    }

    public function testAddAndGetHandlers(): void
    {
        $handlers = [
            $this->createMock(EntityApiHandlerInterface::class),
            $this->createMock(EntityApiHandlerInterface::class)
        ];

        foreach ($handlers as $handler) {
            $this->processor->addHandler($handler);
        }

        $this->assertEquals(
            $handlers,
            $this->processor->getHandlers()
        );
    }

    public function testGetHandlerByClass(): void
    {
        $entity = new SomeEntity();
        $handler = $this->createMock(EntityApiHandlerInterface::class);
        $this->processor->addHandler($handler);

        $handler->expects($this->once())
            ->method('getClass')
            ->willReturn(ClassUtils::getClass($entity));

        $this->assertEquals(
            $handler,
            $this->processor->getHandlerByClass(ClassUtils::getClass($entity))
        );
    }

    public function testGetHandlerByClassNull(): void
    {
        $entity = new SomeEntity();
        $handler = $this->createMock(EntityApiHandlerInterface::class);
        $this->processor->addHandler($handler);

        $handler->expects($this->once())
            ->method('getClass')
            ->willReturn(ClassUtils::getClass($entity));

        $this->assertEquals(
            null,
            $this->processor->getHandlerByClass('test/class')
        );
    }

    public function testPreProcess(): void
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'preProcess', 1);

        $this->processor->preProcess($entity);
    }

    public function testPreProcessSkipp(): void
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'preProcess');

        $this->processor->preProcess($entity);
    }

    public function testBeforeProcess(): void
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'beforeProcess', 1);

        $this->processor->beforeProcess($entity);
    }

    public function testBeforeProcessSkipp(): void
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'beforeProcess');

        $this->processor->beforeProcess($entity);
    }

    public function testAfterProcess(): void
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'afterProcess', 1);

        $this->processor->afterProcess($entity);
    }

    public function testAfterProcessSkipp(): void
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'afterProcess');

        $this->processor->invalidateProcess($entity);
    }

    public function testInvalidateProcess(): void
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'invalidateProcess', 1);

        $this->processor->invalidateProcess($entity);
    }

    public function testInvalidateProcessSkipp(): void
    {
        $entity = new SomeEntity();
        $this->prepareProcess($entity, 'invalidateProcess');

        $this->processor->invalidateProcess($entity);
    }

    private function prepareProcess(object $entity, string $method, int $count = 0): void
    {
        $handler = $this->createMock(EntityApiHandlerInterface::class);
        $this->processor->addHandler($handler);

        if ($count === 0) {
            $handler->expects($this->once())
                ->method('getClass')
                ->willReturn('some/class');
        } else {
            $handler->expects($this->once())
                ->method('getClass')
                ->willReturn(ClassUtils::getClass($entity));
        }

        $handler->expects($this->exactly($count))
            ->method($method);
    }
}

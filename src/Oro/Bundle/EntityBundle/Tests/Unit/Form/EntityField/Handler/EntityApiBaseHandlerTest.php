<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\EntityField\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\Form\EntityField\Handler\EntityApiBaseHandler;
use Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor\EntityApiHandlerProcessor;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub\SomeEntity;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Symfony\Component\Form\FormInterface;

class EntityApiBaseHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityApiHandlerProcessor|\PHPUnit\Framework\MockObject\MockObject */
    private $processor;

    /** @var EntityApiBaseHandler */
    private $handler;

    /** @var Registry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EntityClassNameHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassNameHelper;

    public function methodsDataProvider(): array
    {
        return [
            'POST' => ['POST', true],
            'PUT' => ['PUT', true],
            'PATCH' => ['PATCH', false],
        ];
    }

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->processor = $this->createMock(EntityApiHandlerProcessor::class);
        $this->entityClassNameHelper = $this->createMock(EntityClassNameHelper::class);

        $this->handler = new EntityApiBaseHandler($this->registry, $this->processor, $this->entityClassNameHelper);
    }

    public function testProcessUnsupportedMethod()
    {
        $entity = new SomeEntity();
        $form = $this->createMock(FormInterface::class);
        $data = ['a' => 1];
        $method = 'UNSUP';

        $this->processor->expects($this->once())
            ->method('preProcess')
            ->with($entity);
        $form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $form->expects($this->never())
            ->method('submit')
            ->with($data);

        $this->assertEquals([], $this->handler->process($entity, $form, $data, $method));
    }

    /**
     * @dataProvider methodsDataProvider
     * @param string $method
     * @param bool $clearMissing
     */
    public function testProcessDataEmpty($method, $clearMissing)
    {
        $entity = new SomeEntity();
        $form = $this->createMock(FormInterface::class);
        $data = [];

        $this->processor->expects($this->once())
            ->method('preProcess')
            ->with($entity);
        $form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $form->expects($this->once())
            ->method('submit')
            ->with($data, $clearMissing);

        $this->assertEquals([], $this->handler->process($entity, $form, $data, $method));
    }

    /**
     * @dataProvider methodsDataProvider
     * @param string $method
     * @param bool $clearMissing
     */
    public function testProcessInvalid($method, $clearMissing)
    {
        $entity = new SomeEntity();
        $form = $this->createMock(FormInterface::class);
        $data = ['a' => '1', 'b' => '2'];

        $this->processor->expects($this->once())
            ->method('preProcess')
            ->with($entity);
        $form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $form->expects($this->once())
            ->method('submit')
            ->with($data, $clearMissing);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $this->processor->expects($this->never())
            ->method('beforeProcess')
            ->with($entity);
        $this->processor->expects($this->never())
            ->method('afterProcess')
            ->with($entity);
        $this->processor->expects($this->once())
            ->method('invalidateProcess')
            ->with($entity);

        $this->assertEquals([], $this->handler->process($entity, $form, $data, $method));
    }

    /**
     * @dataProvider methodsDataProvider
     * @param string $method
     * @param bool $clearMissing
     */
    public function testProcessValid($method, $clearMissing)
    {
        $entity = new SomeEntity();
        $form = $this->createMock(FormInterface::class);
        $data = ['a' => '1', 'b' => '2'];

        $this->processor->expects($this->once())
            ->method('preProcess')
            ->with($entity);
        $form->expects($this->once())
            ->method('setData')
            ->with($entity);
        $form->expects($this->once())
            ->method('submit')
            ->with($data, $clearMissing);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->processor->expects($this->once())
            ->method('beforeProcess')
            ->with($entity);
        $this->processor->expects($this->once())
            ->method('afterProcess')
            ->with($entity);
        $this->processor->expects($this->never())
            ->method('invalidateProcess')
            ->with($entity);

        $this->initManager();

        $this->assertEquals([
            'fields' => [
                'a' => '1',
                'b' => '2'
            ]
        ], $this->handler->process($entity, $form, $data, $method));
    }

    private function initManager()
    {
        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->any())
            ->method('persist');
        $manager->expects($this->any())
            ->method('flush');

        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($manager);
    }
}

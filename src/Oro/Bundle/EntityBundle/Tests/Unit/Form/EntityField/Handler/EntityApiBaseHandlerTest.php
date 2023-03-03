<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\EntityField\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Form\EntityField\Handler\EntityApiBaseHandler;
use Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor\EntityApiHandlerProcessor;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub\SomeEntity;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class EntityApiBaseHandlerTest extends TestCase
{
    /** @var EntityApiHandlerProcessor|\PHPUnit\Framework\MockObject\MockObject */
    private $processor;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntityApiBaseHandler */
    private $handler;

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
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->processor = $this->createMock(EntityApiHandlerProcessor::class);

        $this->handler = new EntityApiBaseHandler(
            $this->doctrine,
            $this->processor,
            PropertyAccess::createPropertyAccessor()
        );
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
     */
    public function testProcessDataEmpty(string $method, bool $clearMissing)
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
     */
    public function testProcessInvalid(string $method, bool $clearMissing)
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
     */
    public function testProcessValid(string $method, bool $clearMissing)
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

        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($manager);
    }
}

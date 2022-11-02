<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Action\CreateRelatedEntity;
use Oro\Component\Action\Exception\ActionException;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CreateRelatedEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var CreateRelatedEntity */
    private $action;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->action = new CreateRelatedEntity(new ContextAccessor(), $this->registry);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    public function testInitializeException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Object data must be an array.');

        $options = [
            'data' => 'test'
        ];
        $this->action->initialize($options);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options)
    {
        self::assertSame($this->action, $this->action->initialize($options));
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    public function optionsDataProvider(): array
    {
        return [
            [[]],
            [['data' => null]],
            [['data' => ['test' => 'data']]],
        ];
    }

    public function testExecuteExceptionInterface()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Context must be instance of WorkflowItem');

        $context = new \stdClass();
        $this->action->execute($context);
    }

    public function testExecuteExceptionNotManaged()
    {
        $this->expectException(NotManageableEntityException::class);
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects(self::once())
            ->method('getRelatedEntity')
            ->willReturn(\stdClass::class);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getDefinition')
            ->willReturn($definition);

        $this->registry->expects(self::once())
            ->method('getManagerForClass');

        $this->action->execute($workflowItem);
    }

    public function testExecuteSaveException()
    {
        $this->expectException(ActionException::class);
        $this->expectExceptionMessage(sprintf("Can't create related entity %s.", \stdClass::class));

        $entity = new \stdClass();
        $entity->test = null;

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects(self::once())
            ->method('getRelatedEntity')
            ->willReturn(\stdClass::class);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);
        $workflowItem->expects(self::once())
            ->method('getDefinition')
            ->willReturn($definition);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('persist')
            ->with($entity)
            ->willThrowException(new \Exception());

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->action->initialize([]);
        $this->action->execute($workflowItem);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $options)
    {
        $entity = new \stdClass();
        $entity->test = null;

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects(self::once())
            ->method('getRelatedEntity')
            ->willReturn(\stdClass::class);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity);
        $workflowItem->expects(self::once())
            ->method('getDefinition')
            ->willReturn($definition);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('persist')
            ->with($entity);
        $em->expects(self::once())
            ->method('flush')
            ->with($entity);

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->action->initialize($options);
        $this->action->execute($workflowItem);
    }
}

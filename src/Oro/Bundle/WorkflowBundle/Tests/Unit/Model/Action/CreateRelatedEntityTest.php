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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CreateRelatedEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|ContextAccessor */
    protected $contextAccessor;

    /** @var MockObject|ManagerRegistry */
    protected $registry;

    /** @var CreateRelatedEntity */
    protected $action;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();

        $this->action = new class($this->contextAccessor, $this->registry) extends CreateRelatedEntity {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
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
     * @param array $options
     */
    public function testInitialize($options)
    {
        static::assertSame($this->action, $this->action->initialize($options));
        static::assertEquals($options, $this->action->xgetOptions());
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
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
        $relatedEntity = '\stdClass';
        $definition = $this->getMockBuilder(WorkflowDefinition::class)->disableOriginalConstructor()->getMock();
        $definition->expects(static::once())->method('getRelatedEntity')->willReturn($relatedEntity);

        $workflowItem = $this->getMockBuilder(WorkflowItem::class)->disableOriginalConstructor()->getMock();
        $workflowItem->expects(static::once())->method('getDefinition')->willReturn($definition);

        $this->registry->expects(static::once())->method('getManagerForClass');
        $this->action->execute($workflowItem);
    }

    public function testExecuteSaveException()
    {
        $this->expectException(ActionException::class);
        $this->expectExceptionMessage("Can't create related entity \stdClass.");

        $relatedEntity = '\stdClass';
        $entity = new \stdClass();
        $entity->test = null;

        $definition = $this->getMockBuilder(WorkflowDefinition::class)->disableOriginalConstructor()->getMock();
        $definition->expects(static::once())->method('getRelatedEntity')->willReturn($relatedEntity);

        $workflowItem = $this->getMockBuilder(WorkflowItem::class)->disableOriginalConstructor()->getMock();
        $workflowItem->expects(static::once())->method('getEntity')->willReturn($entity);
        $workflowItem->expects(static::once())->method('getDefinition')->willReturn($definition);

        $em = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $em->expects(static::once())
            ->method('persist')
            ->with($entity)
            ->willReturnCallback(
                function () {
                    throw new \Exception();
                }
            );

        $this->registry->expects(static::once())->method('getManagerForClass')->willReturn($em);

        $this->action->initialize([]);
        $this->action->execute($workflowItem);
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     */
    public function testExecute($options)
    {
        $relatedEntity = '\stdClass';
        $entity = new \stdClass();
        $entity->test = null;

        $definition = $this->getMockBuilder(WorkflowDefinition::class)->disableOriginalConstructor()->getMock();
        $definition->expects(static::once())->method('getRelatedEntity')->willReturn($relatedEntity);

        $workflowItem = $this->getMockBuilder(WorkflowItem::class)->disableOriginalConstructor()->getMock();
        $workflowItem->expects(static::once())->method('getEntity')->willReturn($entity);
        $workflowItem->expects(static::once())->method('getDefinition')->willReturn($definition);

        $em = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $em->expects(static::once())->method('persist')->with($entity);
        $em->expects(static::once())->method('flush')->with($entity);

        $this->registry->expects(static::once())->method('getManagerForClass')->willReturn($em);

        $this->action->initialize($options);
        $this->action->execute($workflowItem);
    }
}

<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionSchedule;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\TransitionQueryFactory;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;

use Symfony\Bridge\Doctrine\ManagerRegistry;

class TransitionQueryFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var TransitionQueryFactory */
    protected $queryFactory;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->queryFactory = new TransitionQueryFactory($this->registry);
    }

    public function testCreateQuery()
    {
        $transitionName = 'transition_with_schedule';
        $additionalWhereClauseDql = 'e.id = 42';
        $relatedEntity = EntityStub::class;

        /**@var Workflow|\PHPUnit_Framework_MockObject_MockObject $workflow */
        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $stepsManager = new StepManager(
            [
                (new Step())->setName('step_one')->setAllowedTransitions(['trans_other', $transitionName]),
                (new Step())->setName('step_two')->setAllowedTransitions(['trans_other', $transitionName])
            ]
        );
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();

        $repository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        //identifier
        $this->registry->expects($this->at(0))
            ->method('getManagerForClass')
            ->with($relatedEntity)
            ->willReturn($entityManager);

        $metadataInfo = new ClassMetadataInfo($relatedEntity);
        $metadataInfo->setIdentifier(['id']);
        $entityManager->expects($this->once())->method('getClassMetadata')->with($relatedEntity)
            ->willReturn($metadataInfo);

        $this->registry->expects($this->at(1))->method('getManagerForClass')->with(WorkflowItem::class)
            ->willReturn($entityManager);

        //retrieve WorkflowItem QueryBuilder
        $entityManager->expects($this->once())->method('getRepository')->with(WorkflowItem::class)
            ->willReturn($repository);
        $repository->expects($this->once())->method('createQueryBuilder')->with('wi')
            ->willReturn($queryBuilder);

        $workflow->expects($this->once())->method('getStepManager')->willReturn($stepsManager);

        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn((new WorkflowDefinition())->setRelatedEntity($relatedEntity));

        $queryBuilder->expects($this->at(0))->method('select')->with('wi.id')
            ->willReturnSelf();
        $queryBuilder->expects($this->at(1))->method('innerJoin')->with('wi.definition', 'wd')
            ->willReturnSelf();
        $queryBuilder->expects($this->at(2))->method('innerJoin')->with('wi.currentStep', 'ws')
            ->willReturnSelf();
        $queryBuilder->expects($this->at(3))
            ->method('innerJoin')->with(
                $relatedEntity,
                'e',
                Join::WITH,
                'wi.entityId = CAST(e.id as text)'
            )->willReturnSelf();

        $queryBuilder->expects($this->at(4))->method('expr')
            ->willReturn(new Expr);

        $queryBuilder->expects($this->at(5))->method('where')->with(
            $this->logicalAnd(
                $this->isInstanceOf(Func::class),
                $this->attributeEqualTo('name', 'ws.name IN'),
                $this->attributeEqualTo('arguments', [':workflowSteps'])
            )
        )->willReturnSelf();

        $queryBuilder->expects($this->at(6))->method('setParameter')->with('workflowSteps', ['step_one', 'step_two']);

        $queryBuilder->expects($this->at(7))->method('andWhere')->with('wd.relatedEntity = :entityClass')
            ->willReturnSelf();

        $queryBuilder->expects($this->at(8))->method('setParameter')->with('entityClass', $relatedEntity);

        $queryBuilder->expects($this->at(9))
            ->method('andWhere')
            ->with($additionalWhereClauseDql);

        $this->queryFactory->create($workflow, $transitionName, $additionalWhereClauseDql);
    }
}

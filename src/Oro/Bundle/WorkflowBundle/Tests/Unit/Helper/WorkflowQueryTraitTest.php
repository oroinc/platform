<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait;

class WorkflowQueryTraitTest extends \PHPUnit_Framework_TestCase
{
    use WorkflowQueryTrait;

    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryBuilder;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject */
    protected $classMetadata;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->classMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
    }

    // joinWorkflowItem call tested implicitly
    public function testJoinWorkflowStepOnDry()
    {
        //when no workflowItem alias comes
        $this->queryBuilder->expects($this->at(0))->method('getAllAliases')->willReturn(['entityClass1']);

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with('entityClass1')
            ->willReturn($this->classMetadata);

        $this->classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['ident1', 'ident2']);

        $this->queryBuilder->expects($this->at(1))
            ->method('getRootEntities')
            ->willReturn(['entityClass1', 'entityClass2']);

        $this->queryBuilder->expects($this->at(2))
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->queryBuilder->expects($this->at(3))
            ->method('getRootAliases')
            ->willReturn(['rootAlias']);

        $this->queryBuilder->expects($this->at(4))
            ->method('leftJoin')
            ->with(
                WorkflowItem::class,
                'itemAlias',
                Join::WITH,
                sprintf('CAST(rootAlias.ident1 as string) = CAST(itemAlias.entityId as string)' .
                    ' AND itemAlias.entityClass = \'entityClass1\'')
            )
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->at(5))
            ->method('leftJoin')
            ->with('itemAlias.currentStep', 'stepAlias')->willReturn($this->queryBuilder);

        $this->assertSame(
            $this->queryBuilder,
            $this->joinWorkflowStep($this->queryBuilder, 'stepAlias', 'itemAlias')
        );
    }

    public function testJoinWorkflowStepOnWet()
    {
        //when workflowItem was already joined to an alias provided
        $this->queryBuilder->expects($this->at(0))->method('getAllAliases')->willReturn(['entityClass1', 'itemAlias']);

        $this->queryBuilder->expects($this->at(1))
            ->method('leftJoin')
            ->with('itemAlias.currentStep', 'stepAlias')->willReturn($this->queryBuilder);

        $this->assertSame(
            $this->queryBuilder,
            $this->joinWorkflowStep($this->queryBuilder, 'stepAlias', 'itemAlias')
        );
    }

    public function testAddDatagridQuery()
    {
        $query = [
            'var1' => 'value1',
            'join' => ['left' => ['join1']],
        ];

        $this->assertEquals(
            [
                'var1' => 'value1',
                'join' => [
                    'left' => [
                        'join1',
                        [
                            'join' => WorkflowItem::class,
                            'alias' => 'itemAlias',
                            'conditionType' => Join::WITH,
                            'condition' => 'CAST(entityAlias.entityIdent as string) =' .
                                ' CAST(itemAlias.entityId as string) AND itemAlias.entityClass = \'entityClass\'',
                        ],
                        [
                            'join' => 'itemAlias.currentStep',
                            'alias' => 'stepAlias',
                        ],
                    ],
                ],
            ],
            $this->addDatagridQuery(
                $query,
                'entityAlias',
                'entityClass',
                'entityIdent',
                'stepAlias',
                'itemAlias'
            )
        );
    }
}

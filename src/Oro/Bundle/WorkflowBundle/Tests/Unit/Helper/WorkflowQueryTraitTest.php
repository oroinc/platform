<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait;

class WorkflowQueryTraitTest extends \PHPUnit\Framework\TestCase
{
    use WorkflowQueryTrait;

    const ENTITY_CLASS = 'SomeEntityClass';

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $queryBuilder;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
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

        $this->queryBuilder->expects($this->once())
            ->method('getRootEntities')
            ->willReturn(['entityClass1', 'entityClass2']);

        $this->queryBuilder->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->queryBuilder->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['rootAlias']);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('leftJoin')
            ->withConsecutive(
                [
                    WorkflowItem::class,
                    'itemAlias',
                    Join::WITH,
                    sprintf('CAST(rootAlias.ident1 as string) = CAST(itemAlias.entityId as string)' .
                        ' AND itemAlias.entityClass = \'entityClass1\'')
                ],
                [
                    'itemAlias.currentStep',
                    'stepAlias'
                ]
            )
            ->willReturn($this->queryBuilder);

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

    /**
     * @dataProvider identifierFieldTypeDataProvider
     * @param string $identifierFieldType
     * @param string $identifierFieldName
     * @param string $entityAlias
     * @param string $itemAlias
     * @param string $expectedCondition
     */
    public function testJoinWorkflowItem(
        $identifierFieldType,
        $identifierFieldName,
        $entityAlias,
        $itemAlias,
        $expectedCondition
    ) {
        /** @var WorkflowQueryTrait $trait */
        $trait = static::getMockForTrait(WorkflowQueryTrait::class);

        $this->classMetadata
            ->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn([$identifierFieldName]);

        $this->classMetadata
            ->expects($this->any())
            ->method('getTypeOfField')
            ->with($identifierFieldName)
            ->willReturn($identifierFieldType);

        $this->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->classMetadata);

        $this->queryBuilder
            ->expects($this->any())
            ->method('getRootEntities')
            ->willReturn([self::ENTITY_CLASS]);

        $this->queryBuilder
            ->expects($this->any())
            ->method('getRootAliases')
            ->willReturn([$entityAlias]);

        $this->queryBuilder
            ->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->queryBuilder
            ->expects($this->once())
            ->method('leftJoin')
            ->with(
                WorkflowItem::class,
                $itemAlias,
                Join::WITH,
                $expectedCondition
            );

        $trait->joinWorkflowItem($this->queryBuilder, $itemAlias);
    }

    /**
     * @return array
     */
    public function identifierFieldTypeDataProvider()
    {
        return [
            [
                'identifierFieldType' => Type::INTEGER,
                'identifierFieldName' => 'idField',
                'entityAlias' => 't',
                'itemAlias' => 'workflowAlias',
                'expectedCondition' => sprintf(
                    't.idField = CAST(workflowAlias.entityId as int) AND workflowAlias.entityClass = \'%s\'',
                    self::ENTITY_CLASS
                )
            ],
            [
                'identifierFieldType' => Type::STRING,
                'identifierFieldName' => 'idField',
                'entityAlias' => 'rootAlias',
                'itemAlias' => 'workflowAlias',
                'expectedCondition' => sprintf(
                    'CAST(rootAlias.idField as string) = CAST(workflowAlias.entityId as string) ' .
                    'AND workflowAlias.entityClass = \'%s\'',
                    self::ENTITY_CLASS
                )
            ]
        ];
    }
}

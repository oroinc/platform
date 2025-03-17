<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowQueryTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowQueryTraitTest extends TestCase
{
    use WorkflowQueryTrait;

    private const ENTITY_CLASS = 'SomeEntityClass';

    private QueryBuilder&MockObject $queryBuilder;
    private EntityManagerInterface&MockObject $entityManager;
    private ClassMetadata&MockObject $classMetadata;

    #[\Override]
    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
    }

    // joinWorkflowItem call tested implicitly
    public function testJoinWorkflowStepOnDry(): void
    {
        // when no workflowItem alias comes
        $this->queryBuilder->expects(self::once())
            ->method('getAllAliases')
            ->willReturn(['entityClass1']);

        $this->entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with('entityClass1')
            ->willReturn($this->classMetadata);

        $this->classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['ident1', 'ident2']);

        $this->queryBuilder->expects(self::once())
            ->method('getRootEntities')
            ->willReturn(['entityClass1', 'entityClass2']);

        $this->queryBuilder->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->queryBuilder->expects(self::once())
            ->method('getRootAliases')
            ->willReturn(['rootAlias']);

        $this->queryBuilder->expects(self::exactly(2))
            ->method('leftJoin')
            ->withConsecutive(
                [
                    WorkflowItem::class,
                    'itemAlias',
                    Join::WITH,
                    'CAST(rootAlias.ident1 as string) = CAST(itemAlias.entityId as string)'
                        . ' AND itemAlias.entityClass = \'entityClass1\''
                ],
                [
                    'itemAlias.currentStep',
                    'stepAlias'
                ]
            )
            ->willReturn($this->queryBuilder);

        self::assertSame(
            $this->queryBuilder,
            $this->joinWorkflowStep($this->queryBuilder, 'stepAlias', 'itemAlias')
        );
    }

    public function testJoinWorkflowStepOnWet(): void
    {
        // when workflowItem was already joined to an alias provided
        $this->queryBuilder->expects(self::once())
            ->method('getAllAliases')
            ->willReturn(['entityClass1', 'itemAlias']);

        $this->queryBuilder->expects(self::once())
            ->method('leftJoin')
            ->with('itemAlias.currentStep', 'stepAlias')->willReturn($this->queryBuilder);

        self::assertSame(
            $this->queryBuilder,
            $this->joinWorkflowStep($this->queryBuilder, 'stepAlias', 'itemAlias')
        );
    }

    public function testAddDatagridQuery(): void
    {
        $query = [
            'var1' => 'value1',
            'join' => ['left' => ['join1']],
        ];

        self::assertEquals(
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
     */
    public function testJoinWorkflowItem(
        string $identifierFieldType,
        string $identifierFieldName,
        string $entityAlias,
        string $itemAlias,
        string $expectedCondition
    ): void {
        $trait = $this->getMockForTrait(WorkflowQueryTrait::class);

        $this->classMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn([$identifierFieldName]);
        $this->classMetadata->expects(self::any())
            ->method('getTypeOfField')
            ->with($identifierFieldName)
            ->willReturn($identifierFieldType);

        $this->entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->classMetadata);

        $this->queryBuilder->expects(self::any())
            ->method('getRootEntities')
            ->willReturn([self::ENTITY_CLASS]);
        $this->queryBuilder->expects(self::any())
            ->method('getRootAliases')
            ->willReturn([$entityAlias]);
        $this->queryBuilder->expects(self::any())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);
        $this->queryBuilder->expects(self::once())
            ->method('leftJoin')
            ->with(
                WorkflowItem::class,
                $itemAlias,
                Join::WITH,
                $expectedCondition
            );

        $trait->joinWorkflowItem($this->queryBuilder, $itemAlias);
    }

    public function identifierFieldTypeDataProvider(): array
    {
        return [
            [
                'identifierFieldType' => Types::INTEGER,
                'identifierFieldName' => 'idField',
                'entityAlias' => 't',
                'itemAlias' => 'workflowAlias',
                'expectedCondition' => sprintf(
                    't.idField = CAST(workflowAlias.entityId as int) AND workflowAlias.entityClass = \'%s\'',
                    self::ENTITY_CLASS
                )
            ],
            [
                'identifierFieldType' => Types::STRING,
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

<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Model;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubEntity;

class ScopeCriteriaTest extends \PHPUnit\Framework\TestCase
{
    /** @var ClassMetadataFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $classMetadataFactory;

    protected function setUp(): void
    {
        $this->classMetadataFactory = $this->createMock(ClassMetadataFactory::class);
    }

    public function testGetIdentifier()
    {
        $entityIdReflProperty = new \ReflectionProperty(StubEntity::class, 'id');
        $entityIdReflProperty->setAccessible(true);
        $entityClassMetadata = new ClassMetadata(StubEntity::class);
        $entityClassMetadata->fieldMappings = ['id' => []];
        $entityClassMetadata->reflFields = ['id' => $entityIdReflProperty];
        $entityClassMetadata->setIdentifier(['id']);
        $this->classMetadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->with(StubEntity::class)
            ->willReturn($entityClassMetadata);
        $newEntity1 = new StubEntity(null);
        $newEntity2 = new StubEntity(null);
        $newEntity3 = new StubEntity(null);
        $criteria = new ScopeCriteria(
            [
                'nullField' => null,
                'notNullField' => ScopeCriteria::IS_NOT_NULL,
                'fieldWithValue' => 1,
                'multiField' => [1, 2],
                'newEntity' => $newEntity1,
                'newEntities' => [$newEntity2, $newEntity3],
                'entity' => new StubEntity(10),
                'entities' => [new StubEntity(20), new StubEntity(30)]
            ],
            $this->classMetadataFactory
        );
        $this->assertEquals(
            sprintf(
                'nullField=;notNullField=IS_NOT_NULL;fieldWithValue=1;multiField=1,2;'
                . 'newEntity=%s;newEntities=%s,%s;entity=10;entities=20,30;',
                spl_object_hash($newEntity1),
                spl_object_hash($newEntity2),
                spl_object_hash($newEntity3)
            ),
            $criteria->getIdentifier()
        );
    }

    public function testToArray()
    {
        $criteriaData = [
            'field1' => 1,
            'field2' => 2
        ];
        $criteria = new ScopeCriteria($criteriaData, $this->createMock(ClassMetadataFactory::class));
        $this->assertSame($criteriaData, $criteria->toArray());
    }

    public function testGetIterator()
    {
        $criteriaData = [
            'field1' => 1,
            'field2' => 2
        ];
        $criteria = new ScopeCriteria($criteriaData, $this->createMock(ClassMetadataFactory::class));
        $this->assertSame($criteriaData, iterator_to_array($criteria));
    }

    public function testApplyWhere()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $scopeClassMetadata = new ClassMetadata(Scope::class);
        $scopeClassMetadata->associationMappings = [
            'nullField' => ['type' => ClassMetadata::MANY_TO_ONE],
            'notNullField' => ['type' => ClassMetadata::MANY_TO_ONE],
            'fieldWithValue' => ['type' => ClassMetadata::MANY_TO_ONE],
            'ignoredField' => ['type' => ClassMetadata::MANY_TO_ONE],
            'multiField' => ['type' => ClassMetadata::MANY_TO_MANY]
        ];
        $this->classMetadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(Scope::class)
            ->willReturn($scopeClassMetadata);
        $criteria = new ScopeCriteria(
            [
                'nullField' => null,
                'notNullField' => ScopeCriteria::IS_NOT_NULL,
                'fieldWithValue' => 1,
                'ignoredField' => 2,
                'multiField' => [1, 2],
            ],
            $this->classMetadataFactory
        );
        $qb->method('expr')->willReturn(new Expr());
        $qb->expects($this->exactly(4))
            ->method('andWhere')
            ->withConsecutive(
                ['scope.nullField IS NULL'],
                ['scope.notNullField IS NOT NULL'],
                ['scope.fieldWithValue = :scope_param_fieldWithValue'],
                [new Expr\Func('scope_multiField.id IN', [':scope_multiField_param_id'])]
            );

        $qb->expects($this->exactly(2))
        ->method('setParameter')
        ->withConsecutive(
            ['scope_param_fieldWithValue', 1],
            ['scope_multiField_param_id', [1, 2]]
        );

        $criteria->applyWhere($qb, 'scope', ['ignoredField']);
    }

    public function testApplyWhereWithPriority()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $this->classMetadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(Scope::class)
            ->willReturn(new ClassMetadata(Scope::class));
        $criteria = new ScopeCriteria(
            [
                'nullField' => null,
                'notNullField' => ScopeCriteria::IS_NOT_NULL,
                'fieldWithValue' => 1,
                'ignoredField' => 2,
            ],
            $this->classMetadataFactory
        );
        $qb->method('expr')->willReturn(new Expr());
        $qb->expects($this->exactly(3))
            ->method('andWhere')
            ->withConsecutive(
                ['scope.nullField IS NULL'],
                ['scope.notNullField IS NOT NULL'],
                [new Expr\Orx([
                    new Expr\Comparison('scope.fieldWithValue', '=', ':scope_param_fieldWithValue'),
                    'scope.fieldWithValue IS NULL'
                ])]
            );
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('scope_param_fieldWithValue', 1);

        $criteria->applyWhereWithPriority($qb, 'scope', ['ignoredField']);
    }

    public function testApplyToJoin()
    {
        $this->classMetadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->with(Scope::class)
            ->willReturn(new ClassMetadata(Scope::class));
        $criteria = new ScopeCriteria(
            [
                'joinField' => 5,
                'nullField' => null,
                'notNullField' => ScopeCriteria::IS_NOT_NULL,
                'fieldWithValue' => 1,
                'ignoredField' => 2,
            ],
            $this->classMetadataFactory
        );

        $qb = $this->getBaseQbMock();

        $qb->expects($this->once())
            ->method('innerJoin')
            ->with(
                Scope::class,
                'scope',
                Join::WITH,
                'scope.joinField = 1 AND scope.nullField IS NULL '
                .'AND scope.notNullField IS NOT NULL AND scope.fieldWithValue = :scope_param_fieldWithValue',
                'id'
            );

        $qb->expects($this->once())
            ->method('leftJoin')
            ->with(
                \stdClass::class,
                'otherJoin',
                Join::WITH,
                'otherJoin.joinField = 1',
                'otherJoin.created_at'
            );

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('scope_param_fieldWithValue', 1);

        $criteria->applyToJoin($qb, 'scope', ['ignoredField']);
    }

    public function testApplyToJoinWithPriority()
    {
        $scopeClassMetadata = new ClassMetadata(Scope::class);
        $scopeClassMetadata->associationMappings = [
            'nullField' => ['type' => ClassMetadata::MANY_TO_ONE],
            'notNullField' => ['type' => ClassMetadata::MANY_TO_ONE],
            'fieldWithValue' => ['type' => ClassMetadata::MANY_TO_ONE],
            'ignoredField' => ['type' => ClassMetadata::MANY_TO_ONE],
            'multiField' => ['type' => ClassMetadata::MANY_TO_MANY]
        ];
        $this->classMetadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->with(Scope::class)
            ->willReturn($scopeClassMetadata);
        $criteria = new ScopeCriteria(
            [
                'joinField' => 5,
                'nullField' => null,
                'notNullField' => ScopeCriteria::IS_NOT_NULL,
                'fieldWithValue' => 1,
                'ignoredField' => 2,
                'multiField' => [1, 2],
            ],
            $this->classMetadataFactory
        );
        $qb = $this->getBaseQbMock();

        $qb->expects($this->once())
            ->method('innerJoin')
            ->with(
                Scope::class,
                'scope',
                Join::WITH,
                '(scope.joinField = 1) AND (scope.nullField IS NULL)'
                . ' AND (scope.notNullField IS NOT NULL)'
                . ' AND (scope.fieldWithValue = :scope_param_fieldWithValue OR scope.fieldWithValue IS NULL)',
                'id'
            );

        $qb->expects($this->exactly(2))
            ->method('leftJoin')
            ->withConsecutive(
                [
                    'scope.multiField',
                    'scope_multiField',
                    Join::WITH,
                    new Expr\Orx(
                        [
                            new Expr\Func('scope_multiField.id IN', [':scope_multiField_param_id']),
                            'scope_multiField.id IS NULL'
                        ]
                    )
                ],
                [
                    \stdClass::class,
                    'otherJoin',
                    Join::WITH,
                    '(otherJoin.joinField = 1)',
                    'otherJoin.created_at'
                ]
            );
        $qb->expects($this->exactly(2))
            ->method('addOrderBy')
            ->withConsecutive(
                ['scope.fieldWithValue', 'DESC'],
                ['scope_multiField.id', 'DESC']
            );

        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['scope_param_fieldWithValue', 1],
                ['scope_multiField_param_id', [1, 2]]
            );

        $criteria->applyToJoinWithPriority($qb, 'scope', ['ignoredField']);
    }

    /**
     * @return QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getBaseQbMock()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('expr')->willReturn(new Expr());

        $qb->expects($this->once())
            ->method('resetDQLPart')
            ->with('join');

        $qb->expects($this->once())
            ->method('getDQLPart')
            ->with('join')
            ->willReturn(
                [
                    'joinGroup' => [
                        new Expr\Join(
                            Expr\Join::INNER_JOIN,
                            Scope::class,
                            'scope',
                            Join::WITH,
                            'scope.joinField = 1',
                            'id'
                        ),
                        new Expr\Join(
                            Expr\Join::LEFT_JOIN,
                            \stdClass::class,
                            'otherJoin',
                            Join::WITH,
                            'otherJoin.joinField = 1',
                            'otherJoin.created_at'
                        ),
                    ],
                ]
            );

        return $qb;
    }
}

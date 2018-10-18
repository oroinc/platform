<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Model;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\Join;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ScopeCriteriaTest extends \PHPUnit\Framework\TestCase
{
    public function testToArray()
    {
        $criteriaData = [
            'field1' => 1,
            'field2' => 2,
        ];
        $criteria = new ScopeCriteria($criteriaData, []);
        $this->assertSame($criteriaData, $criteria->toArray());
    }

    public function testApplyWhere()
    {
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $criteria = new ScopeCriteria(
            [
                'nullField' => null,
                'notNullField' => ScopeCriteria::IS_NOT_NULL,
                'fieldWithValue' => 1,
                'ignoredField' => 2,
                'multiField' => [1, 2],
            ],
            [
                'nullField' => ['relation_type' => 'manyToOne'],
                'notNullField' => ['relation_type' => 'manyToOne'],
                'fieldWithValue' => ['relation_type' => 'manyToOne'],
                'ignoredField' => ['relation_type' => 'manyToOne'],
                'multiField' => ['relation_type' => 'manyToMany'],
            ]
        );
        $qb->method('expr')->willReturn(new Expr());
        $qb->expects($this->exactly(4))
            ->method('andWhere')
            ->withConsecutive(
                ['scope.nullField IS NULL'],
                ['scope.notNullField IS NOT NULL'],
                ['scope.fieldWithValue = :scope_param_fieldWithValue'],
                new Expr\Func('scope_multiField.id IN', [':scope_multiField_param_id'])
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
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();

        $criteria = new ScopeCriteria(
            [
                'nullField' => null,
                'notNullField' => ScopeCriteria::IS_NOT_NULL,
                'fieldWithValue' => 1,
                'ignoredField' => 2,
            ],
            []
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
        $criteria = new ScopeCriteria(
            [
                'joinField' => 5,
                'nullField' => null,
                'notNullField' => ScopeCriteria::IS_NOT_NULL,
                'fieldWithValue' => 1,
                'ignoredField' => 2,
            ],
            []
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
        $criteria = new ScopeCriteria(
            [
                'joinField' => 5,
                'nullField' => null,
                'notNullField' => ScopeCriteria::IS_NOT_NULL,
                'fieldWithValue' => 1,
                'ignoredField' => 2,
                'multiField' => [1, 2],
            ],
            [
                'nullField' => ['relation_type' => 'manyToOne'],
                'notNullField' => ['relation_type' => 'manyToOne'],
                'fieldWithValue' => ['relation_type' => 'manyToOne'],
                'ignoredField' => ['relation_type' => 'manyToOne'],
                'multiField' => ['relation_type' => 'manyToMany'],
            ]
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
    protected function getBaseQbMock()
    {
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
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

    /**
     * @param $platform
     * @param $qb
     */
    protected function platformIsCalled($platform, $qb)
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $qb->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);
    }
}

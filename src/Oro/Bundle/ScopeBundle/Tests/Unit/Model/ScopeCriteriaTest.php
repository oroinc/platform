<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Model;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\Join;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ScopeCriteriaTest extends \PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
        $criteriaData = [
            'field1' => 1,
            'field2' => 2,
        ];
        $criteria = new ScopeCriteria($criteriaData);
        $this->assertSame($criteriaData, $criteria->toArray());
    }

    public function testApplyWhere()
    {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $qb */
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $criteria = new ScopeCriteria(
            [
                'nullField' => null,
                'notNullField' => ScopeCriteria::IS_NOT_NULL,
                'fieldWithValue' => 1,
                'ignoredField' => 2,
            ]
        );
        $qb->method('expr')->willReturn(new Expr());
        $qb->expects($this->exactly(3))
            ->method('andWhere')
            ->withConsecutive(
                ['scope.nullField IS NULL'],
                ['scope.notNullField IS NOT NULL'],
                ['scope.fieldWithValue = :scope_param_fieldWithValue']
            );
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('scope_param_fieldWithValue', 1);

        $criteria->applyWhere($qb, 'scope', ['ignoredField']);
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
            ]
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

        $qb->expects($this->once())
            ->method('leftJoin')
            ->with(
                \stdClass::class,
                'otherJoin',
                Join::WITH,
                '(otherJoin.joinField = 1)',
                'otherJoin.created_at'
            );
        $qb->expects($this->once())
            ->method('addOrderBy')
            ->with('scope.fieldWithValue', 'DESC');

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('scope_param_fieldWithValue', 1);

        $criteria->applyToJoinWithPriority($qb, 'scope', ['ignoredField']);
    }

    /**
     * @return QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBaseQbMock()
    {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $qb */
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
}

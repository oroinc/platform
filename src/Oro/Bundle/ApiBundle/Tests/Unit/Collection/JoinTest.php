<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\Join;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JoinTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorWithAllParameters()
    {
        $join = new Join(
            Join::INNER_JOIN,
            'Test\Entity',
            Join::WITH,
            'entity.field = 123',
            'idx_test'
        );

        self::assertNull($join->getAlias());
        self::assertEquals(Join::INNER_JOIN, $join->getJoinType());
        self::assertEquals('Test\Entity', $join->getJoin());
        self::assertEquals(Join::WITH, $join->getConditionType());
        self::assertEquals('entity.field = 123', $join->getCondition());
        self::assertEquals('idx_test', $join->getIndexBy());
    }

    /**
     * @dataProvider conditionTypeNormalizationDataProvider
     */
    public function testConditionTypeNormalization($conditionType, $condition)
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity', $conditionType, $condition);
        self::assertNull($join->getConditionType());
    }

    public function conditionTypeNormalizationDataProvider()
    {
        return [
            ['', null],
            ['', ''],
            [Join::ON, null],
            [Join::ON, ''],
            [Join::WITH, null],
            [Join::WITH, '']
        ];
    }

    /**
     * @dataProvider conditionNormalizationDataProvider
     */
    public function testConditionNormalization($condition)
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity', Join::WITH, $condition);
        self::assertNull($join->getCondition());
    }

    public function conditionNormalizationDataProvider()
    {
        return [
            [null],
            ['']
        ];
    }

    /**
     * @dataProvider indexByNormalizationDataProvider
     */
    public function testIndexByNormalization($indexBy)
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity', Join::WITH, 'condition', $indexBy);
        self::assertNull($join->getIndexBy());
    }

    public function indexByNormalizationDataProvider()
    {
        return [
            [null],
            ['']
        ];
    }

    public function testSetJoin()
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity');

        $join->setJoin('Test\Entity1');
        self::assertEquals('Test\Entity1', $join->getJoin());
    }

    public function testSetJoinType()
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity');

        $join->setJoinType(Join::INNER_JOIN);
        self::assertSame(Join::INNER_JOIN, $join->getJoinType());
    }

    public function testSetCondition()
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity');

        $join->setCondition('test condition');
        self::assertEquals('test condition', $join->getCondition());
    }

    public function testSetAlias()
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity');

        $join->setAlias('alias');
        self::assertSame('alias', $join->getAlias());
    }

    /**
     * @dataProvider equalsDataProvider
     */
    public function testEquals(Join $join1, Join $join2, $expectedResult)
    {
        self::assertEquals($expectedResult, $join1->equals($join2));
        self::assertEquals($expectedResult, $join2->equals($join1));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function equalsDataProvider()
    {
        return [
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition', 'indexBy'),
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition', 'indexBy'),
                true
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition', 'indexBy'),
                new Join(Join::LEFT_JOIN, 'entity', Join::WITH, 'condition', 'indexBy'),
                true
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition', 'indexBy'),
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition', 'indexBy1'),
                false
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition', 'indexBy'),
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition'),
                false
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition', 'indexBy'),
                new Join(Join::INNER_JOIN, 'entity', Join::WITH),
                false
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition', 'indexBy'),
                new Join(Join::INNER_JOIN, 'entity'),
                false
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition'),
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition'),
                true
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition'),
                new Join(Join::LEFT_JOIN, 'entity', Join::WITH, 'condition'),
                true
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition'),
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition1'),
                false
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition'),
                new Join(Join::INNER_JOIN, 'entity', Join::ON, 'condition'),
                false
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition'),
                new Join(Join::INNER_JOIN, 'entity', Join::WITH),
                false
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH, 'condition'),
                new Join(Join::INNER_JOIN, 'entity'),
                false
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH),
                new Join(Join::INNER_JOIN, 'entity', Join::WITH),
                true
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH),
                new Join(Join::LEFT_JOIN, 'entity', Join::WITH),
                true
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH),
                new Join(Join::INNER_JOIN, 'entity', Join::ON),
                true
            ],
            [
                new Join(Join::INNER_JOIN, 'entity', Join::WITH),
                new Join(Join::INNER_JOIN, 'entity'),
                true
            ],
            [
                new Join(Join::INNER_JOIN, 'entity'),
                new Join(Join::INNER_JOIN, 'entity'),
                true
            ],
            [
                new Join(Join::INNER_JOIN, 'entity'),
                new Join(Join::LEFT_JOIN, 'entity'),
                true
            ],
            [
                new Join(Join::INNER_JOIN, 'entity'),
                new Join(Join::INNER_JOIN, 'entity1'),
                false
            ]
        ];
    }
}

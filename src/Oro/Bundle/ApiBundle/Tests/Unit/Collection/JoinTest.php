<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\Join;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JoinTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
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
    public function testConditionTypeNormalization(string $conditionType, ?string $condition): void
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity', $conditionType, $condition);
        self::assertNull($join->getConditionType());
    }

    public function conditionTypeNormalizationDataProvider(): array
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
    public function testConditionNormalization(?string $condition): void
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity', Join::WITH, $condition);
        self::assertNull($join->getCondition());
    }

    public function conditionNormalizationDataProvider(): array
    {
        return [
            [null],
            ['']
        ];
    }

    /**
     * @dataProvider indexByNormalizationDataProvider
     */
    public function testIndexByNormalization(?string $indexBy): void
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity', Join::WITH, 'condition', $indexBy);
        self::assertNull($join->getIndexBy());
    }

    public function indexByNormalizationDataProvider(): array
    {
        return [
            [null],
            ['']
        ];
    }

    public function testSetJoin(): void
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity');

        $join->setJoin('Test\Entity1');
        self::assertEquals('Test\Entity1', $join->getJoin());
    }

    public function testSetJoinType(): void
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity');

        $join->setJoinType(Join::INNER_JOIN);
        self::assertSame(Join::INNER_JOIN, $join->getJoinType());
    }

    public function testSetCondition(): void
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity');

        $join->setCondition('test condition');
        self::assertEquals('test condition', $join->getCondition());
    }

    public function testSetAlias(): void
    {
        $join = new Join(Join::LEFT_JOIN, 'Test\Entity');

        $join->setAlias('alias');
        self::assertSame('alias', $join->getAlias());
    }

    /**
     * @dataProvider equalsDataProvider
     */
    public function testEquals(Join $join1, Join $join2, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $join1->equals($join2));
        self::assertSame($expectedResult, $join2->equals($join1));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function equalsDataProvider(): array
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

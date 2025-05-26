<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\ComputedFieldsWhereExpressionModifier;

class ComputedFieldsWhereExpressionModifierTest extends OrmRelatedTestCase
{
    private const string ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    private function assertQuery(string $expectedDql, QueryBuilder $qb): void
    {
        (new ComputedFieldsWhereExpressionModifier())->updateQuery($qb);

        self::assertEquals(
            $expectedDql,
            str_replace(self::ENTITY_NAMESPACE, 'Test:', $qb->getDQL())
        );
    }

    public function testNoComputedFields(): void
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');
        $qb->where($qb->expr()->eq('e.name', ':name'));
        $this->assertQuery(
            'SELECT e FROM Test:User e WHERE e.name = :name',
            $qb
        );
    }

    public function testNoComputedFieldInSelect(): void
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');
        $qb->where($qb->expr()->eq('e.name', ':name'));
        $qb->andWhere($qb->expr()->eq('hasName', ':prm'));
        $this->assertQuery(
            'SELECT e FROM Test:User e WHERE e.name = :name AND hasName = :prm',
            $qb
        );
    }

    /**
     * @dataProvider computedFieldDataProvider
     */
    public function testComputedFieldInAndExpr(
        string $selectExpr,
        string $whereExpr,
        string $fieldName
    ): void {
        $qb = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');
        $qb->addSelect($selectExpr);
        $qb->where($qb->expr()->eq('e.name', ':name'));
        $qb->andWhere($qb->expr()->eq($fieldName, ':prm'));
        $this->assertQuery(
            'SELECT e, ' . $selectExpr
            . ' FROM Test:User e'
            . ' WHERE e.name = :name AND ' . $whereExpr . ' = :prm',
            $qb
        );
    }

    /**
     * @dataProvider computedFieldDataProvider
     */
    public function testComputedFieldInOrExpr(
        string $selectExpr,
        string $whereExpr,
        string $fieldName
    ): void {
        $qb = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');
        $qb->addSelect($selectExpr);
        $qb->where($qb->expr()->eq('e.name', ':name'));
        $qb->orWhere($qb->expr()->eq($fieldName, ':prm'));
        $this->assertQuery(
            'SELECT e, ' . $selectExpr
            . ' FROM Test:User e'
            . ' WHERE e.name = :name OR ' . $whereExpr . ' = :prm',
            $qb
        );
    }

    /**
     * @dataProvider computedFieldDataProvider
     */
    public function testComputedFieldInComplexExpr(
        string $selectExpr,
        string $whereExpr,
        string $fieldName
    ): void {
        $qb = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');
        $qb->addSelect($selectExpr);
        $qb->where($qb->expr()->eq('e.name', ':name'));
        $qb->orWhere($qb->expr()->eq($fieldName, ':prm'));
        $qb->andWhere($qb->expr()->eq('e.owner', ':owner'));
        $this->assertQuery(
            'SELECT e, ' . $selectExpr
            . ' FROM Test:User e'
            . ' WHERE (e.name = :name OR ' . $whereExpr . ' = :prm) AND e.owner = :owner',
            $qb
        );
    }

    public function computedFieldDataProvider(): array
    {
        return [
            [
                '(CASE WHEN e.name IS NULL THEN false ELSE true END) AS hasName',
                '(CASE WHEN e.name IS NULL THEN false ELSE true END)',
                'hasName'
            ],
            [
                '(case when e.name is null then false else true end) as hasName',
                '(case when e.name is null then false else true end)',
                'hasName'
            ],
            ['e.name AS name_1', 'e.name', 'name_1'],
            ['e.name as name_1', 'e.name', 'name_1'],
            ['e.name AS name-1', 'e.name', 'name-1'],
            ['e.name as name-1', 'e.name', 'name-1'],
        ];
    }

    public function testSeveralComputedFields(): void
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Entity\User::class, 'e');
        $qb->addSelect('e.name AS name1');
        $qb->addSelect('e.owner AS owner');
        $qb->where($qb->expr()->eq('e.name', ':name'));
        $qb->andWhere($qb->expr()->eq('name1', ':name1'));
        $qb->andWhere($qb->expr()->eq('owner', ':owner'));
        $this->assertQuery(
            'SELECT e, e.name AS name1, e.owner AS owner'
            . ' FROM Test:User e'
            . ' WHERE e.name = :name AND e.name = :name1 AND e.owner = :owner',
            $qb
        );
    }
}

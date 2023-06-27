<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Component\DoctrineUtils\ORM\UnionQueryBuilder;
use Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity;
use Oro\Component\TestUtils\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UnionQueryBuilderTest extends OrmTestCase
{
    /** @var EntityManager */
    private $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(
            new AnnotationReader(),
            'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity'
        ));
        $this->em->getConfiguration()->setEntityNamespaces([
            'Test' => 'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity'
        ]);
    }

    public function testConstructorWithDefaultArguments()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertTrue($qb->getUnionAll());
        self::assertEquals('entity', $qb->getAlias());
    }

    public function testConstructor()
    {
        $qb = new UnionQueryBuilder($this->em, false, 'alias');
        self::assertFalse($qb->getUnionAll());
        self::assertEquals('alias', $qb->getAlias());
    }

    public function testSetAlias()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertSame($qb, $qb->setAlias('alias'));
        self::assertEquals('alias', $qb->getAlias());
    }

    public function testSetUnionAll()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertSame($qb, $qb->setUnionAll(false));
        self::assertFalse($qb->getUnionAll());
    }

    public function testGetDefaultFirstResult()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertNull($qb->getFirstResult());
    }

    public function testSetFirstResult()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertSame($qb, $qb->setFirstResult(100));
        self::assertSame(100, $qb->getFirstResult());
    }

    public function testGetDefaultMaxResults()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertNull($qb->getMaxResults());
    }

    public function testSetMaxResults()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertSame($qb, $qb->setMaxResults(100));
        self::assertSame(100, $qb->getMaxResults());
    }

    public function testGetDefaultOrderBy()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertNull($qb->getOrderBy());
    }

    public function testAddOrderBy()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertSame($qb, $qb->addOrderBy('column1'));
        self::assertSame($qb, $qb->addOrderBy('column2', 'DESC'));
        self::assertSame(
            ['column1' => null, 'column2' => 'DESC'],
            $qb->getOrderBy()
        );
    }

    public function testGetDefaultSelect()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertSame([], $qb->getSelect());
    }

    public function testAddSelect()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertSame($qb, $qb->addSelect('column1', 'alias1'));
        self::assertSame($qb, $qb->addSelect('column2', 'alias2', Types::INTEGER));
        self::assertSame(
            [
                ['column1', 'alias1', Types::STRING],
                ['column2', 'alias2', Types::INTEGER]
            ],
            $qb->getSelect()
        );
    }

    public function testGetDefaultSubQueries()
    {
        $qb = new UnionQueryBuilder($this->em);
        self::assertSame([], $qb->getSubQueries());
    }

    public function testAddSubQuery()
    {
        $qb = new UnionQueryBuilder($this->em);

        $query1 = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->getQuery();
        $query2 = $this->em->getRepository(Entity\Person::class)
            ->createQueryBuilder('e')
            ->getQuery();

        self::assertSame($qb, $qb->addSubQuery($query1));
        self::assertSame($qb, $qb->addSubQuery($query2));
        self::assertSame(
            [$query1, $query2],
            $qb->getSubQueries()
        );
    }

    public function testGetQueryBuilderWhenNoSubQueries()
    {
        $this->expectException(\Doctrine\DBAL\Query\QueryException::class);
        $this->expectExceptionMessage('At least one sub-query should be added.');

        $qb = new UnionQueryBuilder($this->em);

        $qb->getQueryBuilder();
    }

    public function testGetQueryBuilderWhenNoSelectExpr()
    {
        $this->expectException(\Doctrine\DBAL\Query\QueryException::class);
        $this->expectExceptionMessage('At least one select expression should be added.');

        $qb = new UnionQueryBuilder($this->em);

        $subQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb->getQuery());

        $qb->getQueryBuilder();
    }

    public function testGetQueryBuilderForOnlyOneSubQuery()
    {
        $qb = new UnionQueryBuilder($this->em);
        $qb->addSelect('id', 'id', Types::INTEGER);

        $subQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb->getQuery());

        self::assertEquals(
            'SELECT entity.id_0 AS id FROM ('
            . '(SELECT g0_.id AS id_0 FROM Group g0_)'
            . ') entity',
            $qb->getQueryBuilder()->getQuery()->getSQL()
        );
    }

    public function testGetQueryBuilderForSeveralSubQuery()
    {
        $qb = new UnionQueryBuilder($this->em);
        $qb->addSelect('id', 'id', Types::INTEGER);

        $subQb1 = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb1->getQuery());

        $subQb2 = $this->em->getRepository(Entity\Person::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb2->getQuery());

        self::assertEquals(
            'SELECT entity.id_0 AS id FROM ('
            . '(SELECT g0_.id AS id_0 FROM Group g0_)'
            . ' UNION ALL '
            . '(SELECT p0_.id AS id_0 FROM Person p0_)'
            . ') entity',
            $qb->getQueryBuilder()->getQuery()->getSQL()
        );
    }

    public function testGetQuery()
    {
        $qb = new UnionQueryBuilder($this->em);
        $qb->addSelect('id', 'id', Types::INTEGER);

        $subQb1 = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb1->getQuery());

        $subQb2 = $this->em->getRepository(Entity\Person::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb2->getQuery());

        self::assertEquals(
            'SELECT entity.id_0 AS id FROM ('
            . '(SELECT g0_.id AS id_0 FROM Group g0_)'
            . ' UNION ALL '
            . '(SELECT p0_.id AS id_0 FROM Person p0_)'
            . ') entity',
            $qb->getQuery()->getSQL()
        );
    }

    public function testGetQueryForUnionInsteadOfUnionAll()
    {
        $qb = new UnionQueryBuilder($this->em, false);
        $qb->addSelect('id', 'id', Types::INTEGER);

        $subQb1 = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb1->getQuery());

        $subQb2 = $this->em->getRepository(Entity\Person::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb2->getQuery());

        self::assertEquals(
            'SELECT entity.id_0 AS id FROM ('
            . '(SELECT g0_.id AS id_0 FROM Group g0_)'
            . ' UNION '
            . '(SELECT p0_.id AS id_0 FROM Person p0_)'
            . ') entity',
            $qb->getQuery()->getSQL()
        );
    }

    public function testGetQueryWhenSubQueryColumnDoesNotEqualToResultColumn()
    {
        $qb = new UnionQueryBuilder($this->em);
        $qb->addSelect('subId', 'resultId', Types::INTEGER);

        $subQb1 = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id AS subId');
        $qb->addSubQuery($subQb1->getQuery());

        $subQb2 = $this->em->getRepository(Entity\Person::class)
            ->createQueryBuilder('e')
            ->select('e.id AS subId');
        $qb->addSubQuery($subQb2->getQuery());

        self::assertEquals(
            'SELECT entity.id_0 AS resultId FROM ('
            . '(SELECT g0_.id AS id_0 FROM Group g0_)'
            . ' UNION ALL '
            . '(SELECT p0_.id AS id_0 FROM Person p0_)'
            . ') entity',
            $qb->getQuery()->getSQL()
        );
    }

    public function testGetQueryWithOrderBy()
    {
        $qb = new UnionQueryBuilder($this->em);
        $qb->addSelect('id', 'id', Types::INTEGER);
        $qb->addOrderBy($qb->getAlias() . '.id', 'DESC');

        $subQb1 = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb1->getQuery());

        $subQb2 = $this->em->getRepository(Entity\Person::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb2->getQuery());

        self::assertEquals(
            'SELECT entity.id_0 AS id FROM ('
            . '(SELECT g0_.id AS id_0 FROM Group g0_)'
            . ' UNION ALL '
            . '(SELECT p0_.id AS id_0 FROM Person p0_)'
            . ') entity'
            . ' ORDER BY entity.id DESC',
            $qb->getQuery()->getSQL()
        );
    }

    public function testGetQueryWithOffset()
    {
        $qb = new UnionQueryBuilder($this->em);
        $qb->addSelect('id', 'id', Types::INTEGER);
        $qb->setFirstResult(100);

        $subQb1 = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb1->getQuery());

        $subQb2 = $this->em->getRepository(Entity\Person::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb2->getQuery());

        self::assertEquals(
            'SELECT entity.id_0 AS id FROM ('
            . '(SELECT g0_.id AS id_0 FROM Group g0_)'
            . ' UNION ALL '
            . '(SELECT p0_.id AS id_0 FROM Person p0_)'
            . ') entity'
            . ' OFFSET 100',
            $qb->getQuery()->getSQL()
        );
    }

    public function testGetQueryWithLimit()
    {
        $qb = new UnionQueryBuilder($this->em);
        $qb->addSelect('id', 'id', Types::INTEGER);
        $qb->setMaxResults(100);

        $subQb1 = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb1->getQuery());

        $subQb2 = $this->em->getRepository(Entity\Person::class)
            ->createQueryBuilder('e')
            ->select('e.id');
        $qb->addSubQuery($subQb2->getQuery());

        self::assertEquals(
            'SELECT entity.id_0 AS id FROM ('
            . '(SELECT g0_.id AS id_0 FROM Group g0_)'
            . ' UNION ALL '
            . '(SELECT p0_.id AS id_0 FROM Person p0_)'
            . ') entity'
            . ' LIMIT 100',
            $qb->getQuery()->getSQL()
        );
    }

    public function testSubQueryWithParameters()
    {
        $qb = new UnionQueryBuilder($this->em);
        $qb->addSelect('id', 'id', Types::INTEGER);

        $subQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.id = :id')
            ->setParameter('id', 123);
        $qb->addSubQuery($subQb->getQuery());

        $query = $qb->getQueryBuilder()->getQuery();
        self::assertEquals(
            'SELECT entity.id_0 AS id FROM ('
            . '(SELECT g0_.id AS id_0 FROM Group g0_ WHERE g0_.id = :q0__id)'
            . ') entity',
            $query->getSQL()
        );
        /** @var ArrayCollection $parameters */
        $parameters = $query->getParameters();
        self::assertEquals(1, $parameters->count());
        $parameter = $parameters->first();
        self::assertEquals('q0__id', $parameter->getName());
        self::assertEquals(123, $parameter->getValue());
    }

    public function testSubQueryWithSortingAndPaging()
    {
        $qb = new UnionQueryBuilder($this->em);
        $qb->addSelect('id', 'id', Types::INTEGER);

        $subQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id')
            ->orderBy('e.id')
            ->setFirstResult(1)
            ->setMaxResults(100);
        $qb->addSubQuery($subQb->getQuery());

        self::assertEquals(
            'SELECT entity.id_0 AS id FROM ('
            . '(SELECT g0_.id AS id_0 FROM Group g0_ ORDER BY g0_.id ASC LIMIT 100 OFFSET 1)'
            . ') entity',
            $qb->getQueryBuilder()->getQuery()->getSQL()
        );
    }

    public function testSubQueryWithSeveralSubQueriesAndParameters(): void
    {
        $qb = new UnionQueryBuilder($this->em);
        $qb->addSelect('id', 'id', Types::INTEGER);

        $subQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.id = :id')
            ->setParameter('id', 123);
        $qb->addSubQuery($subQb->getQuery());

        $subQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.id >= :id')
            ->andWhere('e.id <> :notId')
            ->setParameter('id', 456)
            ->setParameter('notId', 85);
        $qb->addSubQuery($subQb->getQuery());

        $query = $qb->getQueryBuilder()->getQuery();
        self::assertEquals(
            'SELECT entity.id_0 AS id FROM ('
            . '(SELECT g0_.id AS id_0 FROM Group g0_ WHERE g0_.id = :q0__id)'
            . ' UNION ALL'
            . ' (SELECT g0_.id AS id_0 FROM Group g0_ WHERE g0_.id >= :q1__id AND g0_.id <> :q1__notId)) entity',
            $query->getSQL()
        );
        /** @var ArrayCollection $parameters */
        $parameters = $query->getParameters();
        self::assertEquals(3, $parameters->count());
        $parameter = $parameters->first();
        self::assertEquals('q0__id', $parameter->getName());
        self::assertEquals(123, $parameter->getValue());
        $parameter = $parameters->get(1);
        self::assertEquals('q1__id', $parameter->getName());
        self::assertEquals(456, $parameter->getValue());
        $parameter = $parameters->get(2);
        self::assertEquals('q1__notId', $parameter->getName());
        self::assertEquals(85, $parameter->getValue());
    }
}

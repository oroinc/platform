<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\ORM\HookUnionTrait;
use Oro\Component\DoctrineUtils\ORM\SqlWalker;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class SqlWalkerTest extends OrmTestCase
{
    /**
     * @var EntityManager|EntityManagerMock
     */
    protected $em;

    protected function setUp()
    {
        $reader = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Test' => 'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity'
            ]
        );
    }

    /**
     * @dataProvider queryDataProvider
     * @param string $query
     * @param string $expectedQuery
     * @param string $platformClass
     */
    public function testQueryModification($query, $expectedQuery, $platformClass)
    {
        $platform = new $platformClass();
        $this->em->getConnection()->setDatabasePlatform($platform);

        $q = $this->em->createQuery($query);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlWalker::class);

        $this->assertEquals($expectedQuery, $q->getSQL());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function queryDataProvider()
    {
        return [
            'simple query pgsql asc' => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY pId ASC',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY id_0 ASC',
                PostgreSqlPlatform::class
            ],
            'simple query pgsql desc' => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY pId DESC',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY id_0 DESC',
                PostgreSqlPlatform::class
            ],
            'simple with nulls query pgsql asc' => [
                'SELECT p.name FROM Test:Person p ORDER BY p.name ASC',
                'SELECT p0_.name AS name_0 FROM Person p0_ ORDER BY p0_.name ASC NULLS FIRST',
                PostgreSqlPlatform::class
            ],
            'simple with nulls query pgsql desc' => [
                'SELECT p.name FROM Test:Person p ORDER BY p.name DESC',
                'SELECT p0_.name AS name_0 FROM Person p0_ ORDER BY p0_.name DESC NULLS LAST',
                PostgreSqlPlatform::class
            ],
            'join query pgsql asc' => [
                'SELECT i.id as iId FROM Test:Person p JOIN p.bestItem i ORDER BY i.id ASC',
                'SELECT i0_.id AS id_0 FROM Person p1_'
                . ' INNER JOIN Item i0_ ON p1_.bestItem_id = i0_.id ORDER BY i0_.id ASC',
                PostgreSqlPlatform::class
            ],
            'join query pgsql desc' => [
                'SELECT i.id as iId FROM Test:Person p JOIN p.bestItem i ORDER BY iId DESC',
                'SELECT i0_.id AS id_0 FROM Person p1_'
                . ' INNER JOIN Item i0_ ON p1_.bestItem_id = i0_.id ORDER BY id_0 DESC',
                PostgreSqlPlatform::class
            ],
            'join with nulls query pgsql asc' => [
                'SELECT i.name as iName FROM Test:Person p JOIN p.bestItem i ORDER BY iName ASC',
                'SELECT i0_.name AS name_0 FROM Person p1_'
                . ' INNER JOIN Item i0_ ON p1_.bestItem_id = i0_.id ORDER BY name_0 ASC NULLS FIRST',
                PostgreSqlPlatform::class
            ],
            'join with nulls query pgsql desc' => [
                'SELECT p.id FROM Test:Person p JOIN p.bestItem i ORDER BY i.name DESC',
                'SELECT p0_.id AS id_0 FROM Person p0_'
                . ' INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id ORDER BY i1_.name DESC NULLS LAST',
                PostgreSqlPlatform::class
            ],
            'join mixed order by query pgsql' => [
                'SELECT p.id FROM Test:Person p JOIN p.bestItem i ORDER BY i.name DESC, p.id ASC',
                'SELECT p0_.id AS id_0 FROM Person p0_'
                . ' INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id ORDER BY i1_.name DESC NULLS LAST, p0_.id ASC',
                PostgreSqlPlatform::class
            ],
            'join association order by query pgsql' => [
                'SELECT p.id FROM Test:Person p JOIN p.bestItem i ORDER BY p.bestItem',
                'SELECT p0_.id AS id_0 FROM Person p0_'
                . ' INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id ORDER BY p0_.bestItem_id ASC',
                PostgreSqlPlatform::class
            ],
            'join nullable association order by query pgsql' => [
                'SELECT p.id FROM Test:Person p JOIN p.someItem i ORDER BY p.someItem',
                'SELECT p0_.id AS id_0 FROM Person p0_'
                . ' INNER JOIN Item i1_ ON p0_.some_item = i1_.id ORDER BY p0_.some_item ASC NULLS FIRST',
                PostgreSqlPlatform::class
            ],
            'simple query mysql asc' => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY pId ASC',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY id_0 ASC',
                MySqlPlatform::class
            ],
            'simple query mysql desc' => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY pId DESC',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY id_0 DESC',
                MySqlPlatform::class
            ],
            'simple with nulls query mysql asc' => [
                'SELECT p.name FROM Test:Person p ORDER BY p.name ASC',
                'SELECT p0_.name AS name_0 FROM Person p0_ ORDER BY p0_.name ASC',
                MySqlPlatform::class
            ],
            'simple with nulls query mysql desc' => [
                'SELECT p.name FROM Test:Person p ORDER BY p.name DESC',
                'SELECT p0_.name AS name_0 FROM Person p0_ ORDER BY p0_.name DESC',
                MySqlPlatform::class
            ],
            'join query mysql asc' => [
                'SELECT i.id as iId FROM Test:Person p JOIN p.bestItem i ORDER BY i.id ASC',
                'SELECT i0_.id AS id_0 FROM Person p1_'
                . ' INNER JOIN Item i0_ ON p1_.bestItem_id = i0_.id ORDER BY i0_.id ASC',
                MySqlPlatform::class
            ],
            'join query mysql desc' => [
                'SELECT i.id as iId FROM Test:Person p JOIN p.bestItem i ORDER BY iId DESC',
                'SELECT i0_.id AS id_0 FROM Person p1_'
                . ' INNER JOIN Item i0_ ON p1_.bestItem_id = i0_.id ORDER BY id_0 DESC',
                MySqlPlatform::class
            ],
            'join with nulls query mysql asc' => [
                'SELECT i.name as iName FROM Test:Person p JOIN p.bestItem i ORDER BY iName ASC',
                'SELECT i0_.name AS name_0 FROM Person p1_'
                . ' INNER JOIN Item i0_ ON p1_.bestItem_id = i0_.id ORDER BY name_0 ASC',
                MySqlPlatform::class
            ],
            'join with nulls query mysql desc' => [
                'SELECT p.id FROM Test:Person p JOIN p.bestItem i ORDER BY i.name DESC',
                'SELECT p0_.id AS id_0 FROM Person p0_'
                . ' INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id ORDER BY i1_.name DESC',
                MySqlPlatform::class
            ],
            'join mixed order by query mysql' => [
                'SELECT p.id FROM Test:Person p JOIN p.bestItem i ORDER BY i.name DESC, p.id ASC',
                'SELECT p0_.id AS id_0 FROM Person p0_'
                . ' INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id ORDER BY i1_.name DESC, p0_.id ASC',
                MySqlPlatform::class
            ],
            'join association order by query mysql' => [
                'SELECT p.id FROM Test:Person p JOIN p.bestItem i ORDER BY p.bestItem',
                'SELECT p0_.id AS id_0 FROM Person p0_'
                . ' INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id ORDER BY p0_.bestItem_id ASC',
                MySqlPlatform::class
            ],
            'join nullable association order by query mysql' => [
                'SELECT p.id FROM Test:Person p JOIN p.someItem i ORDER BY p.someItem',
                'SELECT p0_.id AS id_0 FROM Person p0_'
                . ' INNER JOIN Item i1_ ON p0_.some_item = i1_.id ORDER BY p0_.some_item ASC',
                MySqlPlatform::class
            ],
        ];
    }

    public function testQueryModificationTurnedOff()
    {
        $platform = new PostgreSqlPlatform();
        $this->em->getConnection()->setDatabasePlatform($platform);

        $q = $this->em->createQuery('SELECT p.name FROM Test:Person p ORDER BY p.name ASC');
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlWalker::class);
        $q->setHint(SqlWalker::HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS, true);

        $this->assertEquals('SELECT p0_.name AS name_0 FROM Person p0_ ORDER BY p0_.name ASC', $q->getSQL());
    }

    public function testWalkSubselectWithoutHooks()
    {
        $query = $this->em->getRepository('Test:Group')->createQueryBuilder('g1_')
            ->select('g1_.id')
            ->getQuery();

        $this->assertEquals('SELECT g0_.id AS id_0 FROM Group g0_', $query->getSQL());
    }

    public function testWalkSubselectWithoutUnionHookInRawSQL()
    {
        $query = $this->em->getRepository('Test:Group')->createQueryBuilder('g1_')
            ->select('g1_.id')
            ->getQuery();

        $unionSQL = 'raw sql';
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlWalker::class);
        $query->setHint(HookUnionTrait::$walkerHookUnionKey, "'union_id' = 'union_id'");
        $query->setHint(HookUnionTrait::$walkerHookUnionValue, $unionSQL);

        $this->assertNotContains($unionSQL, $query->getSQL());
    }

    public function testWalkSubselectWithUnionHook()
    {
        $repository = $this->em->getRepository('Test:Group');
        $subSelect  = $repository->createQueryBuilder('g0_')
            ->select('g0_.id')
            ->where('g0_.id = 1');

        $unionHook  = " AND 'union_id' = 'union_id'";
        $qb         = $repository->createQueryBuilder('g1_');
        $query      = $qb
            ->select('g1_.id')
            ->where($qb->expr()->in('g1_.id', $subSelect->getQuery()->getDQL().$unionHook))
            ->getQuery();

        $unionSQL = 'raw sql';
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlWalker::class);
        $query->setHint(HookUnionTrait::$walkerHookUnionKey, $unionHook);
        $query->setHint(HookUnionTrait::$walkerHookUnionValue, $unionSQL);

        $this->assertEquals(
            'SELECT g0_.id AS id_0 FROM Group g0_ '.
            'WHERE g0_.id IN (SELECT g1_.id FROM Group g1_ WHERE g1_.id = 1 UNION raw sql)',
            $query->getSQL()
        );
    }

    public function testWalkSubselectWithExprAfterUnionHook()
    {
        $groupRepository  = $this->em->getRepository('Test:Group');
        $personRepository = $this->em->getRepository('Test:Person');
        $subSelect        = $groupRepository->createQueryBuilder('g1_')
            ->select('g1_.id')
            ->where('g1_.id = 1');

        $unionHook  = " AND 'union_id' = 'union_id'";
        $qb         = $personRepository->createQueryBuilder('p0_');
        $query      = $qb
            ->select('p0_.id')
            ->where($qb->expr()->in('p0_.id', $subSelect->getQuery()->getDQL().$unionHook.' AND g1_.id = 2'))
            ->getQuery();

        $unionSQL = 'raw sql';
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlWalker::class);
        $query->setHint(HookUnionTrait::$walkerHookUnionKey, $unionHook);
        $query->setHint(HookUnionTrait::$walkerHookUnionValue, $unionSQL);

        $this->assertEquals(
            'SELECT p0_.id AS id_0 FROM Person p0_ '.
            'WHERE p0_.id IN (SELECT g1_.id FROM Group g1_ WHERE g1_.id = 1 AND g1_.id = 2 UNION raw sql)',
            $query->getSQL()
        );
    }
}

<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\ORM;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\Tests\Functional\ORM\Constraint\IsEqualById;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class BufferedIdentityQueryResultIteratorTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            LoadOrganization::class,
            '@OroBatchBundle/Tests/Functional/Fixture/data/buffered_iterator.yml',
        ]);
    }

    /**
     * @param $queryBuilder
     * @return array
     */
    protected function getResultsWithForeachLoop(QueryBuilder $queryBuilder)
    {
        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(3);

        $iteratorResult = [];
        foreach ($iterator as $entity) {
            $iteratorResult[] = $entity;
        }

        $query = $queryBuilder->getQuery();
        $result = $query->execute();

        return array($result, $iteratorResult);
    }

    /**
     * @param $queryBuilder
     * @return array
     */
    protected function getResultsWithWhileLoopRewindFirst(QueryBuilder $queryBuilder)
    {
        $iteratorResult = [];

        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(3);

        $iterator->rewind();
        while ($iterator->valid()) {
            $data = $iterator->current();
            $iteratorResult[] = $data;

            $iterator->next();
        }

        $query = $queryBuilder->getQuery();
        $result = $query->execute();

        return array($result, $iteratorResult);
    }

    /**
     * @param $queryBuilder
     * @return array
     */
    protected function getResultsWithWhileLoopNextFirst(QueryBuilder $queryBuilder)
    {
        $iteratorResult = [];

        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(3);

        /**
         * typically $iterator->rewind() should be called before loop
         * but in case $iterator->next() called first all should be fine too
         */
        $iterator->next();
        while ($iterator->valid()) {
            $data = $iterator->current();
            $iteratorResult[] = $data;

            $iterator->next();
        }

        $query = $queryBuilder->getQuery();
        $result = $query->execute();

        return array($result, $iteratorResult);
    }

    /**
     * Asserts that 2 arrays has equal Root Entity IDs and Order. Joined fields may have different order
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function assertSameById(QueryBuilder $queryBuilder)
    {
        list($expected, $actual) = $this->getResultsWithForeachLoop($queryBuilder);
        static::assertThat($actual, new IsEqualById($expected));

        list($expected, $actual) = $this->getResultsWithWhileLoopRewindFirst($queryBuilder);
        static::assertThat($actual, new IsEqualById($expected));

        list($expected, $actual) = $this->getResultsWithWhileLoopNextFirst($queryBuilder);
        static::assertThat($actual, new IsEqualById($expected));
    }

    /**
     * Asserts 2 datasets are equal
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function assertSameResult(QueryBuilder $queryBuilder)
    {
        list($expected, $actual) = $this->getResultsWithForeachLoop($queryBuilder);
        $this->assertEquals($expected, $actual);

        list($expected, $actual) = $this->getResultsWithWhileLoopRewindFirst($queryBuilder);
        $this->assertEquals($expected, $actual);

        list($expected, $actual) = $this->getResultsWithWhileLoopNextFirst($queryBuilder);
        $this->assertEquals($expected, $actual);
    }

    public function testSimpleQuery()
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        if ($this->isPostgreSql()) {
            // Iterator adds sorting automatically, on PostgreSQL results order may be different without sorting
            $queryBuilder->orderBy('item.id');
        }

        $this->assertSameResult($queryBuilder);
    }

    public function testJoinAndGroup()
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');

        $queryBuilder
            ->select('item.id, item.stringValue, SUM(value.id)')
            ->leftJoin('item.values', 'value')
            ->groupBy('item.id');

        if ($this->isPostgreSql()) {
            // Iterator adds sorting automatically, on PostgreSQL results order may be different without sorting
            $queryBuilder->orderBy('item.id');
        }

        $this->assertSameResult($queryBuilder);
    }

    /**
     * @expectedException \LogicException
     */
    public function testInconsistentKey()
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');

        $queryBuilder
            ->select('item.id, item.stringValue, value.id')
            ->leftJoin('item.values', 'value')
            ->groupBy('value.id');

        $this->assertSameResult($queryBuilder);
    }

    /**
     * When selecting certain fields default hydration will be array
     * In this case result rows may appear in different order after iteration by Iterator
     */
    public function testLeftJoinScalar()
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');

        $queryBuilder
            ->select('item.id, item.stringValue, value.id as vid')
            ->leftJoin('item.values', 'value');

        if ($this->isPostgreSql()) {
            // Iterator adds sorting automatically, on PostgreSQL results order may be different without sorting
            $queryBuilder->orderBy('item.id');
            $this->assertSameById($queryBuilder);
        } else {
            $this->assertSameResult($queryBuilder);
        }
    }

    public function testLeftJoinObject()
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');

        $queryBuilder
            ->select('item, value')
            ->leftJoin('item.values', 'value');

        if ($this->isPostgreSql()) {
            // Iterator adds sorting automatically, on PostgreSQL results order may be different without sorting
            $queryBuilder->orderBy('item.id');
        }

        $this->assertSameResult($queryBuilder);
    }

    public function testWhereScalar()
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');

        $queryBuilder
            ->select('item.id, item.stringValue, value.id as vid')
            ->leftJoin('item.values', 'value')
            ->where('value.id > 15 and item.stringValue != :stringValue')
            ->setParameter('stringValue', 'String Value 3');

        if ($this->isPostgreSql()) {
            // Iterator adds sorting automatically, on PostgreSQL results order may be different without sorting
            $queryBuilder->orderBy('item.id');
            $this->assertSameById($queryBuilder);
        } else {
            $this->assertSameResult($queryBuilder);
        }
    }

    public function testWhereObject()
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');

        $queryBuilder
            ->select('item, value')
            ->leftJoin('item.values', 'value')
            ->where('value.id > 15 and item.stringValue != :stringValue')
            ->setParameter('stringValue', 'String Value 3');

        if ($this->isPostgreSql()) {
            // Iterator adds sorting automatically, on PostgreSQL results order may be different without sorting
            $queryBuilder->orderBy('item.id');
        }

        $this->assertSameResult($queryBuilder);
    }

    /**
     * @dataProvider limitOffsetProvider
     *
     * @param integer $offset
     * @param integer $limit
     */
    public function testLimitOffset($offset, $limit)
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

        if ($this->isPostgreSql()) {
            // Iterator adds sorting automatically, on PostgreSQL results order may be different without sorting
            $queryBuilder->orderBy('item.id');
        }

        $this->assertSameResult($queryBuilder);
    }

    public function limitOffsetProvider()
    {
        $data = [];
        foreach (range(0, 10) as $i) {
            $data[] = [
                'offset' => $i % 5,
                'limit'  => $i * 2,
            ];
        }

        return $data;
    }

    public function testChangingDataset()
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder->where('item.stringValue != :v');
        $queryBuilder->setParameter('v', 'processed');

        $result = $queryBuilder->getQuery()->execute();

        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(3);

        $iteratorResult = [];
        foreach ($iterator as $i => $item) {
            // every few records set one as processed to change initial dataset (ruins default pagination)
            if ($i % 3 == 0) {
                $id = $item->getId();
                $em->getConnection()
                   ->exec("update test_search_item set stringValue = 'processed' where id = {$id}");
            }
            $iteratorResult[] = $item;
        }

        if ($this->isPostgreSql()) {
            // Iterator adds sorting automatically, on PostgreSQL results order may be different without sorting
            $queryBuilder->orderBy('item.id');
        }

        $this->assertEquals($result, $iteratorResult);
    }

    public function testDelete()
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:ItemValue')->createQueryBuilder('value');
        $all = count($queryBuilder->getQuery()->execute());

        //every 3rd row
        $queryBuilder->where('Mod(value.id, 3) = 0');
        $toDelete = count($queryBuilder->getQuery()->execute());


        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(4);

        foreach ($iterator as $item) {
            $id = $item->getId();
            $em->getConnection()
               ->exec("delete from test_search_item_value where id = {$id}");
        }

        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:ItemValue')->createQueryBuilder('value');
        $afterDelete = count($queryBuilder->getQuery()->execute());

        if ($this->isPostgreSql()) {
            // Iterator adds sorting automatically, on PostgreSQL results order may be different without sorting
            $queryBuilder->orderBy('item.id');
        }

        $this->assertEquals($all - $toDelete, $afterDelete);
    }

    /**
     * When selecting certain fields default hydration will be array
     * In this case result rows may appear in different order after iteration by Iterator
     */
    public function testOrderByJoinedFieldScalar()
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder
            ->select('item.id, item.stringValue, item.integerValue')
            ->leftJoin('item.values', 'value')
            ->orderBy('item.stringValue')
            ->where('MOD(value.id, 2) = 0')
            ->orderBy('value.id');

        if ($this->isPostgreSql()) {
            $this->expectException(\LogicException::class);
        }

        $this->assertSameById($queryBuilder);
    }

    /**
     * With Object Hydration there will be no previous problem
     */
    public function testOrderByJoinedFieldObjectHydration()
    {
        $this->markTestSkipped('Random failed test. Should be fixed in BAP-16058');
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder
            ->select('item, value')
            ->leftJoin('item.values', 'value')
            ->orderBy('item.stringValue')
            ->where('MOD(value.id, 2) = 0')
            ->orderBy('value.id');

        if ($this->isPostgreSql()) {
            $this->expectException(\LogicException::class);
        }

        $this->assertSameResult($queryBuilder);
    }

    /**
     * Checks if current DB adapter is PostgreSQL
     *
     * @return bool
     */
    private function isPostgreSql()
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        return $em->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }
}

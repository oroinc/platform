<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\ORM;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Component\Testing\Assert\ArrayContainsConstraint;

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

    public function testSimpleQuery()
    {
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');

        $this->assertSameResult($queryBuilder);
    }

    public function testJoinAndGroup()
    {
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder
            ->select('item.id, item.stringValue, SUM(value.id)')
            ->leftJoin('item.values', 'value')
            ->groupBy('item.id');

        $this->assertSameResult($queryBuilder);
    }

    /**
     * @expectedException \LogicException
     */
    public function testInconsistentKey()
    {
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
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder
            ->select('item.id, item.stringValue, value.id as vid')
            ->leftJoin('item.values', 'value');

        $this->assertSameResult($queryBuilder);
    }

    public function testLeftJoinObject()
    {
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder
            ->select('item, value')
            ->leftJoin('item.values', 'value');

        $this->assertSameResult($queryBuilder);
    }

    public function testWhereScalar()
    {
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder
            ->select('item.id, item.stringValue, value.id as vid')
            ->leftJoin('item.values', 'value')
            ->where('value.id > 15 and item.stringValue != :stringValue')
            ->setParameter('stringValue', 'String Value 3');

        $this->assertSameResult($queryBuilder);
    }

    public function testWhereObject()
    {
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder
            ->select('item, value')
            ->leftJoin('item.values', 'value')
            ->where('value.id > 15 and item.stringValue != :stringValue')
            ->setParameter('stringValue', 'String Value 3');

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
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

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

        self::assertEquals($result, $iteratorResult);
    }

    public function testDelete()
    {
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

        self::assertEquals($all - $toDelete, $afterDelete);
    }

    /**
     * When selecting certain fields default hydration will be array
     * In this case result rows may appear in different order after iteration by Iterator
     */
    public function testOrderByJoinedFieldScalar()
    {
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
            self::expectException(\LogicException::class);
        }

        $this->assertSameByIdWithoutOrder($queryBuilder);
    }

    /**
     * With Object Hydration there will be no previous problem
     */
    public function testOrderByJoinedFieldObjectHydration()
    {
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
            self::expectException(\LogicException::class);
        }

        $this->assertSameResult($queryBuilder);
    }

    /**
     * @param $queryBuilder
     *
     * @return array
     */
    private function getResultsWithForeachLoop(QueryBuilder $queryBuilder)
    {
        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(3);

        $iteratorResult = [];
        foreach ($iterator as $entity) {
            $iteratorResult[] = $entity;
        }

        $query = $queryBuilder->getQuery();
        $result = $query->execute();

        return [$result, $iteratorResult];
    }

    /**
     * @param $queryBuilder
     *
     * @return array
     */
    private function getResultsWithWhileLoopRewindFirst(QueryBuilder $queryBuilder)
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

        return [$result, $iteratorResult];
    }

    /**
     * @param $queryBuilder
     *
     * @return array
     */
    private function getResultsWithWhileLoopNextFirst(QueryBuilder $queryBuilder)
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

        return [$result, $iteratorResult];
    }

    /**
     * Asserts 2 datasets are equal
     *
     * @param QueryBuilder $queryBuilder
     */
    private function assertSameResult(QueryBuilder $queryBuilder)
    {
        list($expected, $actual) = $this->getResultsWithForeachLoop($queryBuilder);
        self::assertSame(count($expected), count($actual));
        self::assertThat($expected, new ArrayContainsConstraint($actual, false));

        list($expected, $actual) = $this->getResultsWithWhileLoopRewindFirst($queryBuilder);
        self::assertSame(count($expected), count($actual));
        self::assertThat($expected, new ArrayContainsConstraint($actual, false));

        list($expected, $actual) = $this->getResultsWithWhileLoopNextFirst($queryBuilder);
        self::assertSame(count($expected), count($actual));
        self::assertThat($expected, new ArrayContainsConstraint($actual, false));
    }

    /**
     * Asserts 2 datasets are equal by comparing only result IDs without taking into account results order.
     *
     * @param QueryBuilder $queryBuilder
     */
    private function assertSameByIdWithoutOrder(QueryBuilder $queryBuilder)
    {
        list($expected, $actual) = $this->getResultsWithForeachLoop($queryBuilder);
        self::compareQueryResultWithIteratorResult($expected, $actual);

        list($expected, $actual) = $this->getResultsWithWhileLoopRewindFirst($queryBuilder);
        self::compareQueryResultWithIteratorResult($expected, $actual);

        list($expected, $actual) = $this->getResultsWithWhileLoopNextFirst($queryBuilder);
        self::compareQueryResultWithIteratorResult($expected, $actual);
    }

    /**
     * @param array $queryResult
     * @param array $iteratorResult
     */
    private function compareQueryResultWithIteratorResult($queryResult, $iteratorResult)
    {
        // Compares datasets expecting each item will contain 'id' field
        $queryResultIds = array_column($queryResult, 'id');
        $iteratorResultIds = array_column($iteratorResult, 'id');
        self::assertSame(count($queryResultIds), count($iteratorResultIds));

        // Sorting results due to result rows may appear in different order after iteration by Iterator
        asort($queryResultIds, SORT_NUMERIC);
        asort($iteratorResultIds, SORT_NUMERIC);

        self::assertSame(
            array_values($queryResultIds),
            array_values($iteratorResultIds)
        );
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

<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\ORM;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\Tests\Functional\ORM\Constraint\IsEqualById;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
    protected function getResults(QueryBuilder $queryBuilder)
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
     * Asserts that 2 arrays has equal Root Entity IDs and Order. Joined fields may have different order
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function assertSameById(QueryBuilder $queryBuilder)
    {
        list ($expected, $actual) = $this->getResults($queryBuilder);

        $constraint = new IsEqualById($expected);
        static::assertThat($actual, $constraint);
    }

    /**
     * Asserts 2 datasets are equal
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function assertSameResult(QueryBuilder $queryBuilder)
    {
        list ($expected, $actual) = $this->getResults($queryBuilder);
        $this->assertEquals($expected, $actual);
    }


    public function testSimpleQuery()
    {
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        if ($this->isPostgre()) {
            // Iterator adds sorting automatically, on Postgre SQL results order may bedifferent without sorting
            $queryBuilder->orderBy('item.id');
        }

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

        if ($this->isPostgre()) {
            // Iterator adds sorting automatically, on Postgre SQL results order may bedifferent without sorting
            $queryBuilder->orderBy('item.id');
        }

        $this->assertSameResult($queryBuilder);
    }

    /**
     * @expectedException \LogicException
     */
    public function testInconsistatKey()
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

        if ($this->isPostgre()) {
            // Iterator adds sorting automatically, on Postgre SQL results order may bedifferent without sorting
            $queryBuilder->orderBy('item.id');
            $this->assertSameById($queryBuilder);
        } else {
            $this->assertSameResult($queryBuilder);
        }
    }

    public function testLeftJoinObject()
    {
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');

        $queryBuilder
            ->select('item, value')
            ->leftJoin('item.values', 'value');

        if ($this->isPostgre()) {
            // Iterator adds sorting automatically, on Postgre SQL results order may bedifferent without sorting
            $queryBuilder->orderBy('item.id');
        }

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

        if ($this->isPostgre()) {
            // Iterator adds sorting automatically, on Postgre SQL results order may bedifferent without sorting
            $queryBuilder->orderBy('item.id');
            $this->assertSameById($queryBuilder);
        } else {
            $this->assertSameResult($queryBuilder);
        }
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

        if ($this->isPostgre()) {
            // Iterator adds sorting automatically, on Postgre SQL results order may bedifferent without sorting
            $queryBuilder->orderBy('item.id');
        }

        $this->assertSameResult($queryBuilder);
    }

    /**
     * @dataProvider limitOffsetProvider
     */
    public function testLimitOffset($offset, $limit)
    {
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

        if ($this->isPostgre()) {
            // Iterator adds sorting automatically, on Postgre SQL results order may bedifferent without sorting
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

        if ($this->isPostgre()) {
            // Iterator adds sorting automatically, on Postgre SQL results order may bedifferent without sorting
            $queryBuilder->orderBy('item.id');
        }

        $this->assertEquals($result, $iteratorResult);
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

        if ($this->isPostgre()) {
            // Iterator adds sorting automatically, on Postgre SQL results order may bedifferent without sorting
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
        $em = $this->getContainer()->get('doctrine');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $em->getRepository('OroTestFrameworkBundle:Item')->createQueryBuilder('item');
        $queryBuilder
            ->select('item.id, item.stringValue, item.integerValue')
            ->leftJoin('item.values', 'value')
            ->orderBy('item.stringValue')
            ->where('MOD(value.id, 2) = 0')
            ->orderBy('value.id');

        if ($this->isPostgre()) {
            $this->expectException(\LogicException::class);
        }

        $this->assertSameById($queryBuilder);
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

        if ($this->isPostgre()) {
            $this->expectException(\LogicException::class);
        }

        $this->assertSameResult($queryBuilder);
    }

    /**
     * Checks if current DB adapter is Postgre SQL
     *
     * @return bool
     */
    private function isPostgre()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        return $em->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }
}

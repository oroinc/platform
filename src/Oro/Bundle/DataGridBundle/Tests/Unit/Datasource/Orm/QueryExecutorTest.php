<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource\Orm;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryExecutor;
use Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Entity\Test as Entity;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class QueryExecutorTest extends OrmTestCase
{
    private EntityManagerInterface $em;
    private QueryExecutor $queryExecutor;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->queryExecutor = new QueryExecutor();
    }

    public function testExecute()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query = new Query($this->em);
        $query->setDQL(sprintf('SELECT t.id, t.name FROM %s t', Entity::class));

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT t0_.id AS id_0, t0_.name AS name_1 FROM test_table t0_',
            [['id_0' => 1, 'name_1' => 'test']]
        );

        $result = $this->queryExecutor->execute($datagrid, $query);
        self::assertEquals([['id' => 1, 'name' => 'test']], $result);
    }

    public function testExecuteWithExecuteFunctionAsClosure()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query = new Query($this->em);
        $query->setDQL(sprintf('SELECT t.id, t.name FROM %s t', Entity::class));
        $executeFunc = function (Query $query) {
            return $query->execute();
        };

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT t0_.id AS id_0, t0_.name AS name_1 FROM test_table t0_',
            [['id_0' => 1, 'name_1' => 'test']]
        );

        $result = $this->queryExecutor->execute($datagrid, $query, $executeFunc);
        self::assertEquals([['id' => 1, 'name' => 'test']], $result);
    }

    public function testExecuteWithExecuteFunctionAsCallable()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $query = new Query($this->em);
        $query->setDQL(sprintf('SELECT t.id, t.name FROM %s t', Entity::class));
        $executeFunc = [$this, 'executeQuery'];

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT t0_.id AS id_0, t0_.name AS name_1 FROM test_table t0_',
            [['id_0' => 1, 'name_1' => 'test']]
        );

        $result = $this->queryExecutor->execute($datagrid, $query, $executeFunc);
        self::assertEquals([['id' => 1, 'name' => 'test']], $result);
    }

    public function executeQuery(Query $query): mixed
    {
        return $query->execute();
    }
}

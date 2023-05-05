<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Grid;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryExecutorInterface;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Grid\ReportQueryExecutor;
use Oro\Component\Testing\TempDirExtension;
use Oro\Component\Testing\Unit\ORM\Mocks\EntityManagerMock;

class ReportQueryExecutorTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private const REPORT_CONNECTION_NAME = 'report_connection';
    private const CUSTOM_REPORT_DATAGRID_PREFIX = 'acme_report_';

    /** @var QueryExecutorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseQueryExecutor;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ReportQueryExecutor */
    private $reportQueryExecutor;

    protected function setUp(): void
    {
        $this->baseQueryExecutor = $this->createMock(QueryExecutorInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->reportQueryExecutor = new ReportQueryExecutor(
            $this->baseQueryExecutor,
            $this->doctrine,
            self::REPORT_CONNECTION_NAME,
            [self::CUSTOM_REPORT_DATAGRID_PREFIX]
        );
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        $config->setProxyDir($this->getTempDir('test_orm_proxies'));
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $eventManager = $this->createMock(EventManager::class);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::any())
            ->method('getEventManager')
            ->willReturn($eventManager);

        return EntityManagerMock::create($connection, $config, $eventManager);
    }

    private function getDatagrid(string $name): DatagridInterface
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::any())
            ->method('getName')
            ->willReturn($name);

        return $datagrid;
    }

    public function testExecuteForNotReportDatagrid()
    {
        $em = $this->getEntityManager();
        $datagrid = $this->getDatagrid('some_grid');
        $query = new Query($em);
        $rows = [['key' => 'value']];

        $this->doctrine->expects(self::never())
            ->method('getConnection');
        $this->baseQueryExecutor->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($datagrid), self::identicalTo($query), self::isNull())
            ->willReturn($rows);

        $result = $this->reportQueryExecutor->execute($datagrid, $query);
        self::assertEquals($rows, $result);
    }

    public function testExecuteWithExecuteFunctionForNotReportDatagrid()
    {
        $em = $this->getEntityManager();
        $datagrid = $this->getDatagrid('some_grid');
        $query = new Query($em);
        $executeFunc = function (Query $query) {
            return $query->execute();
        };
        $rows = [['key' => 'value']];

        $this->doctrine->expects(self::never())
            ->method('getConnection');
        $this->baseQueryExecutor->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($datagrid), self::identicalTo($query), self::identicalTo($executeFunc))
            ->willReturn($rows);

        $result = $this->reportQueryExecutor->execute($datagrid, $query, $executeFunc);
        self::assertEquals($rows, $result);
    }

    /**
     * @dataProvider reportGridNameDataProvider
     */
    public function testExecuteForReportDatagrid(string $gridName)
    {
        $em = $this->getEntityManager();
        $datagrid = $this->getDatagrid($gridName);
        $query = new Query($em);
        $rows = [['key' => 'value']];

        $connection = $em->getConnection();
        $reportConnection = $this->createMock(Connection::class);

        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->with(self::REPORT_CONNECTION_NAME)
            ->willReturn($reportConnection);
        $this->baseQueryExecutor->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($datagrid), self::identicalTo($query), self::isNull())
            ->willReturnCallback(function () use ($rows, $em, $reportConnection) {
                // test that the connection was substitute
                self::assertSame($reportConnection, $em->getConnection());

                return $rows;
            });

        $result = $this->reportQueryExecutor->execute($datagrid, $query);
        self::assertEquals($rows, $result);
        // test that the connection was restored
        self::assertSame($connection, $em->getConnection());
    }

    public function reportGridNameDataProvider(): array
    {
        return [
            [Report::GRID_PREFIX . 'test'],
            [self::CUSTOM_REPORT_DATAGRID_PREFIX . 'test']
        ];
    }

    public function testExecuteWithExecuteFunctionForReportDatagrid()
    {
        $em = $this->getEntityManager();
        $datagrid = $this->getDatagrid(Report::GRID_PREFIX . 'test');
        $query = new Query($em);
        $executeFunc = function (Query $query) {
            return $query->execute();
        };
        $rows = [['key' => 'value']];

        $connection = $em->getConnection();
        $reportConnection = $this->createMock(Connection::class);

        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->with(self::REPORT_CONNECTION_NAME)
            ->willReturn($reportConnection);
        $this->baseQueryExecutor->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($datagrid), self::identicalTo($query), self::identicalTo($executeFunc))
            ->willReturnCallback(function () use ($rows, $em, $reportConnection) {
                // test that the connection was substitute
                self::assertSame($reportConnection, $em->getConnection());

                return $rows;
            });

        $result = $this->reportQueryExecutor->execute($datagrid, $query, $executeFunc);
        self::assertEquals($rows, $result);
        // test that the connection was restored
        self::assertSame($connection, $em->getConnection());
    }

    public function testExecuteShouldRestoreOriginalConnectionEvenIfExceptionHappens()
    {
        $exception = new \Exception('some error');
        $em = $this->getEntityManager();
        $datagrid = $this->getDatagrid(Report::GRID_PREFIX . 'test');
        $query = new Query($em);

        $connection = $em->getConnection();
        $reportConnection = $this->createMock(Connection::class);

        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->with(self::REPORT_CONNECTION_NAME)
            ->willReturn($reportConnection);
        $this->baseQueryExecutor->expects(self::once())
            ->method('execute')
            ->willThrowException($exception);

        try {
            $this->reportQueryExecutor->execute($datagrid, $query);
        } catch (\Exception $e) {
            self::assertSame($exception, $e);
            // test that the connection was restored
            self::assertSame($connection, $em->getConnection());
        }
    }
}

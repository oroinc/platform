<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\ImportExport;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Component\DependencyInjection\ServiceLink;
use PHPUnit\Framework\MockObject\MockObject;

class DatagridExportConnectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ServiceLink|MockObject */
    private $gridManagerLink;

    /** @var DatagridExportConnector */
    private $connector;

    protected function setUp(): void
    {
        $this->gridManagerLink = $this->createMock(ServiceLink::class);
        $this->connector = new class($this->gridManagerLink) extends DatagridExportConnector {
            public function xgetPageSize(): int
            {
                return $this->pageSize;
            }

            public function xgetPage(): int
            {
                return $this->getPage();
            }
        };
    }

    public function testSetImportExportContext()
    {
        $batchSize = 42;
        $gridParameters = ['test-1'];
        $gridParametersBag = new ParameterBag($gridParameters);
        $gridName = 'test-grid';

        $config = DatagridConfiguration::create(['columns' => ['id']]);

        $resultObject = $this->createMock(ResultsObject::class);

        $dataSource = $this->createMock(DatasourceInterface::class);

        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects(self::once())
            ->method('getConfig')->willReturn($config);
        $dataGrid->expects(self::any())
            ->method('getDatasource')
            ->willReturn($dataSource);
        $dataGrid->expects(self::any())
            ->method('getParameters')
            ->willReturn($gridParametersBag);
        $dataGrid->expects(self::any())
            ->method('getData')
            ->willReturn($resultObject);

        $gridManager = $this->createMock(Manager::class);
        $gridManager->expects(self::once())
            ->method('getDatagrid')
            ->willReturn($dataGrid);

        $this->gridManagerLink->expects(self::once())
            ->method('getService')
            ->willReturn($gridManager);

        $context = new Context([
            'pageSize' => $batchSize,
            'gridName' => $gridName,
            'gridParameters' => $gridParameters
        ]);

        $this->connector->setImportExportContext($context);
        $this->connector->count();

        self::assertEquals($batchSize, $this->connector->xgetPageSize());
        self::assertEquals(2, $this->connector->xgetPage());
        self::assertEquals(['id'], $context->getValue('columns'));
    }

    public function testSetImportExportContextWithExactPage(): void
    {
        $batchSize = 42;
        $gridParameters = ['test-1'];
        $gridParametersBag = new ParameterBag($gridParameters);
        $gridName = 'test-grid';

        $config = DatagridConfiguration::create(['columns' => ['id']]);

        $resultObject = $this->createMock(ResultsObject::class);

        $dataSource = $this->createMock(DatasourceInterface::class);

        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects(self::once())
            ->method('getConfig')->willReturn($config);
        $dataGrid->expects(self::any())
            ->method('getDatasource')
            ->willReturn($dataSource);
        $dataGrid->expects(self::any())
            ->method('getParameters')
            ->willReturn($gridParametersBag);
        $dataGrid->expects(self::any())
            ->method('getData')
            ->willReturn($resultObject);

        $gridManager = $this->createMock(Manager::class);
        $gridManager->expects(self::once())
            ->method('getDatagrid')
            ->willReturn($dataGrid);

        $this->gridManagerLink->expects(self::once())
            ->method('getService')
            ->willReturn($gridManager);

        $context = new Context([
            'pageSize' => $batchSize,
            'exactPage' => 42,
            'gridName' => $gridName,
            'gridParameters' => $gridParameters,
        ]);

        $this->connector->setImportExportContext($context);
        $this->connector->count();

        self::assertEquals($batchSize, $this->connector->xgetPageSize());
        self::assertEquals(42, $this->connector->xgetPage());
        self::assertEquals(['id'], $context->getValue('columns'));
    }
}

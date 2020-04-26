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
        };
    }

    public function testSetImportExportContext()
    {
        $batchSize = 42;
        $gridParameters = ['test-1'];
        $gridParametersBag = new ParameterBag($gridParameters);
        $gridName = 'test-grid';

        $config = DatagridConfiguration::create(['columns' => ['id']]);

        /** @var ResultsObject|MockObject $resultObject */
        $resultObject = $this->createMock(ResultsObject::class);

        /** @var DatasourceInterface|MockObject $dataSource */
        $dataSource = $this->createMock(DatasourceInterface::class);

        /** @var Datagrid|MockObject $dataGrid */
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects(static::once())->method('getConfig')->willReturn($config);
        $dataGrid->method('getDatasource')->willReturn($dataSource);
        $dataGrid->method('getParameters')->willReturn($gridParametersBag);
        $dataGrid->method('getData')->willReturn($resultObject);

        /** @var Manager|MockObject $gridManager */
        $gridManager = $this->createMock(Manager::class);
        $gridManager->expects(static::once())->method('getDatagrid')->willReturn($dataGrid);

        $this->gridManagerLink->expects(static::once())->method('getService')->willReturn($gridManager);

        $context = new Context([
            'pageSize' => $batchSize,
            'gridName' => $gridName,
            'gridParameters' => $gridParameters
        ]);

        $this->connector->setImportExportContext($context);
        $this->connector->count();

        static::assertEquals($batchSize, $this->connector->xgetPageSize());
        static::assertEquals(['id'], $context->getValue('columns'));
    }
}

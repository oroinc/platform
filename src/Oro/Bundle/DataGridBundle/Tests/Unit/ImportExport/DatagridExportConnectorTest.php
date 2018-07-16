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

class DatagridExportConnectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ServiceLink|\PHPUnit\Framework\MockObject\MockObject
     */
    private $gridManagerLink;

    /**
     * @var DatagridExportConnector
     */
    private $connector;

    protected function setUp()
    {
        $this->gridManagerLink = $this->createMock(ServiceLink::class);
        $this->connector = new DatagridExportConnector($this->gridManagerLink);
    }

    public function testSetImportExportContext()
    {
        $batchSize = 42;
        $gridParameters = ['test-1'];
        $gridParametersBag = new ParameterBag($gridParameters);
        $gridName = 'test-grid';

        $config = DatagridConfiguration::create(['columns' => ['id']]);

        /** @var ResultsObject|\PHPUnit\Framework\MockObject\MockObject $resultObject */
        $resultObject = $this->createMock(ResultsObject::class);

        /** @var DatasourceInterface|\PHPUnit\Framework\MockObject\MockObject $dataSource */
        $dataSource = $this->createMock(DatasourceInterface::class);

        /** @var Datagrid|\PHPUnit\Framework\MockObject\MockObject $dataGrid */
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);
        $dataGrid->expects($this->any())
            ->method('getDatasource')
            ->willReturn($dataSource);
        $dataGrid->expects($this->any())
            ->method('getParameters')
            ->willReturn($gridParametersBag);
        $dataGrid->expects($this->any())
            ->method('getData')
            ->willReturn($resultObject);

        /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $gridManager */
        $gridManager = $this->createMock(Manager::class);
        $gridManager->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($dataGrid);

        $this->gridManagerLink->expects($this->once())
            ->method('getService')
            ->willReturn($gridManager);

        $context = new Context([
            'pageSize' => $batchSize,
            'gridName' => $gridName,
            'gridParameters' => $gridParameters
        ]);

        $this->connector->setImportExportContext($context);
        $this->connector->count();

        $this->assertAttributeEquals($batchSize, 'pageSize', $this->connector);
        $this->assertEquals(['id'], $context->getValue('columns'));
    }
}

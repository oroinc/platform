<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\ImportExport;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Component\DependencyInjection\ServiceLink;

class DatagridExportConnectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject
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
        $gridName = 'test-grid';

        $config = DatagridConfiguration::create(['columns' => ['id']]);

        /** @var Datagrid|\PHPUnit_Framework_MockObject_MockObject $dataGrid */
        $dataGrid = $this->createMock(Datagrid::class);
        $dataGrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        /** @var Manager|\PHPUnit_Framework_MockObject_MockObject $gridManager */
        $gridManager = $this->createMock(Manager::class);
        $gridManager->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($dataGrid);

        $this->gridManagerLink->expects($this->once())
            ->method('getService')
            ->willReturn($gridManager);

        $context = new Context([
            'batchSize' => $batchSize,
            'gridName' => $gridName,
            'gridParameters' => $gridParameters
        ]);

        $this->connector->setImportExportContext($context);

        $this->assertAttributeEquals($batchSize, 'pageSize', $this->connector);
        $this->assertEquals(['id'], $context->getValue('columns'));
    }
}

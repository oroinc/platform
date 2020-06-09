<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Grid\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\ReportBundle\Grid\EventListener\ColumnCurrencyNormalization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ColumnCurrencyNormalizationTest extends TestCase
{
    /** @var DatagridInterface | MockObject */
    private $datagrid;

    /** @var DatagridConfiguration | MockObject */
    private $config;

    protected function setUp(): void
    {
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->config = $this->createMock(DatagridConfiguration::class);
    }

    public function testOnBuildBefore()
    {
        $this->datagrid->expects($this->once())
            ->method('getName')
            ->willReturn('oro_report_table_2');

        $this->config->expects($this->once())
            ->method('toArray')
            ->willReturn([
                'columns' => [
                    'c1' => [
                        'frontend_type' => PropertyInterface::TYPE_CURRENCY
                    ]
                ]
            ]);

        $this->config->expects($this->once())
            ->method('offsetSet')
            ->with('columns', [
                'c1' => [
                    'frontend_type' => PropertyInterface::TYPE_DECIMAL
                ],
            ]);

        $event = new BuildBefore($this->datagrid, $this->config);
        $listener = new ColumnCurrencyNormalization();

        $listener->onBuildBefore($event);
    }
}

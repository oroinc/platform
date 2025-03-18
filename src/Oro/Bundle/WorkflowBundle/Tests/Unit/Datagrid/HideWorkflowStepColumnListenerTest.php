<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\WorkflowBundle\Datagrid\HideWorkflowStepColumnListener;
use Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener;
use PHPUnit\Framework\TestCase;

final class HideWorkflowStepColumnListenerTest extends TestCase
{
    /**
     * @dataProvider buildBeforeDataProvider
     */
    public function testBuildBefore(array $configuration, string $gridName, array $expected): void
    {
        $listener = new HideWorkflowStepColumnListener();
        $event = $this->createBuildBeforeEvent($configuration, $gridName);
        $listener->onBuildBefore($event);

        self::assertSame($expected, $event->getConfig()->toArray());
    }

    public function buildBeforeDataProvider(): array
    {
        return [
            'workflow step column not defined' => [
                'configuration' => [
                    'columns' => [
                        'test' => ['label' => 'Test'],
                    ]
                ],
                'gridName' => 'test_name',
                'expected' => [
                    'columns' => [
                        'test' => ['label' => 'Test'],
                    ]
                ]
            ],
            'workflow step column defined' => [
                'configuration' => [
                    'columns' => [
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => ['label' => 'Test'],
                    ]
                ],
                'gridName' => 'workflow-test-grid',
                'expected' => [
                    'columns' => [
                        WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN => ['label' => 'Test', 'renderable' => false],
                    ]
                ]
            ]
        ];
    }

    private function createBuildBeforeEvent(array $configuration, string $gridName): BuildBefore
    {
        $config = DatagridConfiguration::create($configuration);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::any())
            ->method('getName')
            ->willReturn($gridName);

        $event = $this->createMock(BuildBefore::class);
        $event->expects(self::any())
            ->method('getConfig')
            ->willReturn($config);
        $event->expects(self::any())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        return $event;
    }
}

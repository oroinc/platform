<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Translation;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\WorkflowBundle\Datagrid\Translation\WorkflowColumnListener;
use PHPUnit\Framework\TestCase;

class WorkflowColumnListenerTest extends TestCase
{
    private WorkflowColumnListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new WorkflowColumnListener();
    }

    public function testOnBuildBefore(): void
    {
        $event = new BuildBefore($this->createMock(DatagridInterface::class), DatagridConfiguration::create([]));

        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'filters' => [
                    'columns' => [
                        WorkflowColumnListener::COLUMN_NAME => [
                            'label' => 'oro.workflow.translation.workflow.label',
                            'type' => 'workflow_translation',
                            'data_name' => 'translationKey',
                        ],
                    ],
                ],
            ],
            $event->getConfig()->toArray()
        );
    }
}

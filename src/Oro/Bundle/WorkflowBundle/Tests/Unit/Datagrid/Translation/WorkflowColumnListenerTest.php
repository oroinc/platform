<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Translation;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use Oro\Bundle\WorkflowBundle\Datagrid\Translation\WorkflowColumnListener;

class WorkflowColumnListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowColumnListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->listener = new WorkflowColumnListener();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->listener);
    }

    public function testOnBuildBefore()
    {
        $event = new BuildBefore($this->getMock(DatagridInterface::class), DatagridConfiguration::create([]));

        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'filters' => [
                    'columns' => [
                        WorkflowColumnListener::COLUMN_NAME => [
                            'label' => 'oro.workflow.translation.workflow.label',
                            'type' => 'workflow',
                            'data_name' => 'translationKey',
                            'enabled' => false,
                        ],
                    ],
                ],
            ],
            $event->getConfig()->toArray()
        );
    }
}

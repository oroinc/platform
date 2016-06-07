<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Model\TransitionSchedule;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcesses;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\ScheduledTransitionProcessName;

class ScheduledTransitionProcessesTest extends WebTestCase
{
    const PROCESS_DEFINITION_ENTITY_CLASS = 'Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ScheduledTransitionProcesses
     */
    protected $scheduledTransitionProcesses;

    protected function setUp()
    {
        $this->initClient();
        $this->registry = $this->getContainer()->get('doctrine');
        $this->scheduledTransitionProcesses = new ScheduledTransitionProcesses(
            $this->registry,
            self::PROCESS_DEFINITION_ENTITY_CLASS
        );

        $this->loadFixtures(
            [
                'Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessDefinitionData',
            ]
        );
    }

    /**
     * @param ScheduledTransitionProcessName $scheduledTransitionProcessName
     * @param ProcessDefinition $expected
     *
     * @dataProvider getDataProvider
     */
    public function testGet(
        ScheduledTransitionProcessName $scheduledTransitionProcessName,
        ProcessDefinition $expected = null
    ) {

    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            [
                new ScheduledTransitionProcessName('NON_EXISTING_WF', 'NON_EXISTING_TR'),
                null
            ],
            [
                new ScheduledTransitionProcessName('wtf2', 'tr2'),
            ],
        ];
    }

    public function testWorkflowRelated()
    {
        $this->assertInternalType(
            'array',
            $this->scheduledTransitionProcesses->workflowRelated('SAMPLE_WORKFLOW_NAME')
        );
    }

    public function testAll()
    {
        $this->assertInternalType('array', $this->scheduledTransitionProcesses->all());
    }
}

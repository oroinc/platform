<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Model\TransitionSchedule;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
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
        $this->repository = new ScheduledTransitionProcesses($this->registry, self::PROCESS_DEFINITION_ENTITY_CLASS);
        $this->loadFixtures(
            [
                'Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessDefinitionData',
            ]
        );
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testGet(ScheduledTransitionProcessName $scheduledTransitionProcessName, $exception = null)
    {
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            [
                new ScheduledTransitionProcessName('wf1', 'tr1'),
                'exception' => new \Exception()
            ],
            [
                new ScheduledTransitionProcessName('wf2', 'tr2'),
                'exception' => null
            ],
        ];
    }

    /**
     * @param string $workflow
     *
     * @dataProvider workflowRelatedDataProvider
     */
    public function testWorkflowRelated($workflow)
    {

    }

    /**
     * @return array
     */
    public function workflowRelatedDataProvider()
    {
        return [];
    }

    public function testAll()
    {
    }
}

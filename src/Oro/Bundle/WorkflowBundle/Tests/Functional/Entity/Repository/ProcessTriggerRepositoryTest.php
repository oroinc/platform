<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessEntities;

/**
 * @dbIsolation
 * @dbReindex
 */
class ProcessTriggerRepositoryTest extends WebTestCase
{
    /**
     * @var ProcessTriggerRepository
     */
    protected $repository;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient();

        $doctrine = $this->getContainer()->get('doctrine');
        $this->loadFixtures(array('Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessEntities'));

        $this->entityManager = $doctrine->getManager();
        $this->repository = $doctrine->getRepository('OroWorkflowBundle:ProcessTrigger');
    }

    public function testEqualTriggers()
    {
        $definition = $this->entityManager->find(
            'OroWorkflowBundle:ProcessDefinition',
            LoadProcessEntities::FIRST_DEFINITION
        );
        $trigger = $this->repository->findOneBy(array('definition' => $definition));

        // test equal (existing) trigger
        $equalTrigger = new ProcessTrigger();
        $equalTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setField(LoadProcessEntities::UPDATE_TRIGGER_FIELD);

        $this->assertEquals($trigger, $this->repository->findEqualTrigger($equalTrigger));
        $this->assertTrue($this->repository->isEqualTriggerExists($equalTrigger));

        // test not equal (not existing) trigger
        $notEqualTrigger = new ProcessTrigger();
        $notEqualTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_CREATE);

        $this->assertNull($this->repository->findEqualTrigger($notEqualTrigger));
        $this->assertFalse($this->repository->isEqualTriggerExists($notEqualTrigger));
    }

    public function testfindAllWithEnabledDefinitions()
    {
        $expectedCount = (int)$this->repository->createQueryBuilder('trigger')
            ->select('COUNT(trigger.id) as triggerCount')
            ->getQuery()
            ->getSingleScalarResult();

        $triggers = $this->repository->findAllWithEnabledDefinitions();
        $this->assertCount($expectedCount, $triggers);

        $definition    = null;
        $previousOrder = null;
        foreach ($triggers as $trigger) {
            $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger', $trigger);

            $definition     = $trigger->getDefinition();
            $executionOrder = $definition->getExecutionOrder();

            if ($previousOrder === null) {
                $previousOrder = $executionOrder;
            }

            $this->assertGreaterThanOrEqual($previousOrder, $executionOrder);
            $previousOrder = $executionOrder;
        }

        // check is the function returns only enabled process definitions
        if ($definition) {
            $definition->setEnabled(false);
            $this->entityManager->persist($definition);
            $this->entityManager->flush();

            $triggersAfter = $this->repository->findAllWithEnabledDefinitions();
            $this->assertLessThan(count($triggers), count($triggersAfter));
        }
    }
}

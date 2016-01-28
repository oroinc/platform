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

    public function testFindAllWithDefinitions()
    {
        // all definitions
        $triggers = $this->repository->findAllWithDefinitions();
        $this->assertCount($this->getTriggersCount(), $triggers);
        $this->assertTriggersOrder($triggers);

        // enabled definitions
        $triggers = $this->repository->findAllWithDefinitions(true);
        $this->assertCount($this->getTriggersCount(true), $triggers);
        $this->assertTriggersOrder($triggers);

        // disabled definitions
        $triggers = $this->repository->findAllWithDefinitions(false);
        $this->assertCount($this->getTriggersCount(false), $triggers);
        $this->assertTriggersOrder($triggers);
    }

    public function testFindAllCronTriggers()
    {
        $this->assertCount(1, $this->repository->findAllCronTriggers());
    }

    /**
     * @param ProcessTrigger[] $triggers
     */
    protected function assertTriggersOrder(array $triggers)
    {
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
    }

    /**
     * @param bool|null $enabled
     * @return int
     */
    protected function getTriggersCount($enabled = null)
    {
        $queryBuilder = $this->repository->createQueryBuilder('trigger')
            ->select('COUNT(trigger.id) as triggerCount')
            ->innerJoin('trigger.definition', 'definition');

        if (null !== $enabled) {
            $queryBuilder->andWhere('definition.enabled = :enabled')->setParameter('enabled', $enabled);
        }

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }
}

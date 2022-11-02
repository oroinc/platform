<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessEntities;

class ProcessTriggerRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProcessEntities::class]);
    }

    private function getRepository(): ProcessTriggerRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(ProcessTrigger::class);
    }

    public function testEqualTriggers()
    {
        $definition = self::getContainer()->get('doctrine')->getManagerForClass(ProcessDefinition::class)->find(
            ProcessDefinition::class,
            LoadProcessEntities::FIRST_DEFINITION
        );
        $trigger = $this->getRepository()->findOneBy(['definition' => $definition]);

        // test equal (existing) trigger
        $equalTrigger = new ProcessTrigger();
        $equalTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setField(LoadProcessEntities::UPDATE_TRIGGER_FIELD);

        $this->assertEquals($trigger, $this->getRepository()->findEqualTrigger($equalTrigger));
        $this->assertTrue($this->getRepository()->isEqualTriggerExists($equalTrigger));

        // test not equal (not existing) trigger
        $notEqualTrigger = new ProcessTrigger();
        $notEqualTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_CREATE);

        $this->assertNull($this->getRepository()->findEqualTrigger($notEqualTrigger));
        $this->assertFalse($this->getRepository()->isEqualTriggerExists($notEqualTrigger));
    }

    public function testFindAllWithDefinitions()
    {
        // all definitions
        $triggers = $this->getRepository()->findAllWithDefinitions();
        $this->assertCount($this->getTriggersCount(), $triggers);
        $this->assertTriggersOrder($triggers);

        // enabled definitions
        $triggers = $this->getRepository()->findAllWithDefinitions(true);
        $this->assertCount($this->getTriggersCount(true), $triggers);
        $this->assertTriggersOrder($triggers);

        // disabled definitions
        $triggers = $this->getRepository()->findAllWithDefinitions(false);
        $this->assertCount($this->getTriggersCount(false), $triggers);
        $this->assertTriggersOrder($triggers);

        // without cron triggers
        $triggers = $this->getRepository()->findAllWithDefinitions(false, false);
        $this->assertCount($this->getTriggersCount(false), $triggers);
        $this->assertTriggersOrder($triggers);
    }

    public function testFindAllCronTriggers()
    {
        $triggers = $this->getRepository()->findAllCronTriggers();
        $this->assertContains($this->getReference(LoadProcessEntities::TRIGGER_CRON), $triggers);
        $this->assertNotContains($this->getReference(LoadProcessEntities::TRIGGER_CREATE), $triggers);
    }

    /**
     * @param ProcessTrigger[] $triggers
     */
    private function assertTriggersOrder(array $triggers): void
    {
        $previousOrder = null;
        foreach ($triggers as $trigger) {
            $this->assertInstanceOf(ProcessTrigger::class, $trigger);

            $definition = $trigger->getDefinition();
            $executionOrder = $definition->getExecutionOrder();

            if ($previousOrder === null) {
                $previousOrder = $executionOrder;
            }

            $this->assertGreaterThanOrEqual($previousOrder, $executionOrder);
            $previousOrder = $executionOrder;
        }
    }

    private function getTriggersCount(bool $enabled = null, bool $withCronTriggers = false): int
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('trigger')
            ->select('COUNT(trigger.id) as triggerCount')
            ->innerJoin('trigger.definition', 'definition');

        if (!$withCronTriggers) {
            $queryBuilder->andWhere($queryBuilder->expr()->isNull('trigger.cron'));
            $queryBuilder->andWhere($queryBuilder->expr()->isNotNull('trigger.event'));
        }

        if (null !== $enabled) {
            $queryBuilder->andWhere('definition.enabled = :enabled')->setParameter('enabled', $enabled);
        }

        return (int)$queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function testFindByDefinitionName()
    {
        $firstDefinitionTriggers = $this->getRepository()->findByDefinitionName(LoadProcessEntities::FIRST_DEFINITION);

        $this->assertCount(1, $firstDefinitionTriggers);
        $this->assertContains($this->getReference(LoadProcessEntities::TRIGGER_UPDATE), $firstDefinitionTriggers);
        $this->assertNotContains($this->getReference(LoadProcessEntities::TRIGGER_CREATE), $firstDefinitionTriggers);
        $this->assertNotContains($this->getReference(LoadProcessEntities::TRIGGER_DISABLED), $firstDefinitionTriggers);

        $firstDefinitionTriggers = $this->getRepository()->findByDefinitionName(LoadProcessEntities::SECOND_DEFINITION);

        $this->assertCount(3, $firstDefinitionTriggers);
        $this->assertContains($this->getReference(LoadProcessEntities::TRIGGER_CREATE), $firstDefinitionTriggers);
        $this->assertNotContains($this->getReference(LoadProcessEntities::TRIGGER_UPDATE), $firstDefinitionTriggers);
        $this->assertNotContains($this->getReference(LoadProcessEntities::TRIGGER_DISABLED), $firstDefinitionTriggers);
    }
}

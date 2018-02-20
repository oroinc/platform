<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class LoadWorkflowAwareEntities extends AbstractFixture implements DependentFixtureInterface
{
    const COUNT = 20;

    /** @var int */
    private $lastEntityId = 1;

    /** @var int */
    private $lastItemId = 1;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->generateEntities($manager, [LoadWorkflowDefinitions::NO_START_STEP, LoadWorkflowDefinitions::MULTISTEP]);
        $this->generateEntities($manager, [LoadWorkflowDefinitions::WITH_START_STEP]);
    }

    /**
     * @param ObjectManager $manager
     * @param array $workflowNames
     */
    protected function generateEntities(ObjectManager $manager, array $workflowNames)
    {
        // load entities
        /** @var WorkflowAwareEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= self::COUNT; $i++) {
            $entity = new WorkflowAwareEntity();
            $entity->setName('workflow_aware_entity_' . $this->lastEntityId);
            $entities[] = $entity;
            $manager->persist($entity);

            $this->setReference('workflow_aware_entity.' . $this->lastEntityId, $entity);

            $this->lastEntityId++;
        }
        $manager->flush();

        $workflowDefinitionRepository = $manager->getRepository('OroWorkflowBundle:WorkflowDefinition');

        // create workflow items manually (to make it faster)
        foreach ($workflowNames as $workflowName) {
            $definition = $workflowDefinitionRepository->find($workflowName);
            if ($definition instanceof WorkflowDefinition) {
                $steps = $definition->getSteps()->toArray();

                usort(
                    $steps,
                    function (WorkflowStep $a, WorkflowStep $b) {
                        return strcmp($a->getName(), $b->getName());
                    }
                );

                $firstStep = array_shift($steps);

                foreach ($entities as $entity) {
                    $workflowItem = new WorkflowItem();
                    $workflowItem->setDefinition($definition)
                        ->setEntityId($entity->getId())
                        ->setEntityClass($definition->getRelatedEntity())
                        ->setCurrentStep($firstStep);
                    $manager->persist($workflowItem);

                    $this->setReference($workflowName . '_item.' . $this->lastItemId, $workflowItem);

                    $this->lastItemId++;
                }
            }
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions'];
    }
}

<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

class LoadWorkflowAwareEntities extends AbstractFixture implements DependentFixtureInterface
{
    const COUNT = 20;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $workflowDefinitionRepository = $manager->getRepository('OroWorkflowBundle:WorkflowDefinition');

        $firstDefinition = $workflowDefinitionRepository->find(LoadWorkflowDefinitions::NO_START_STEP);
        $secondDefinition = $workflowDefinitionRepository->find(LoadWorkflowDefinitions::WITH_START_STEP);

        if ($firstDefinition) {
            $this->generateEntities($manager, $firstDefinition);
        }

        if ($secondDefinition) {
            $this->generateEntities($manager, $secondDefinition);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param WorkflowDefinition $definition
     */
    protected function generateEntities(ObjectManager $manager, WorkflowDefinition $definition)
    {
        // load entities
        /** @var WorkflowAwareEntity[] $entities */
        $entities = [];
        for ($i = 1; $i <= self::COUNT; $i++) {
            $entity = new WorkflowAwareEntity();
            $entity->setName($definition->getName() . '_entity_' . $i);
            $entities[$i] = $entity;
            $manager->persist($entity);
            
            $this->setReference('workflow_aware_entity.' . $i, $entity);
        }
        $manager->flush();

        // create workflow item manually (to make it faster)
        foreach ($entities as $i => $entity) {
            $workflowItem = new WorkflowItem();
            $workflowItem->setDefinition($definition)
                ->setWorkflowName($definition->getName())
                ->setEntity($entity)
                ->setEntityId($entity->getId())
                ->setEntityClass($definition->getRelatedEntity())
                ->setCurrentStep($definition->getSteps()->first());
            $manager->persist($workflowItem);

            $this->setReference('workflow_item.' . $i, $workflowItem);
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

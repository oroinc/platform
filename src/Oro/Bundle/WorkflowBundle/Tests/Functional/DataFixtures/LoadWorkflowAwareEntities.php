<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

class LoadWorkflowAwareEntities extends AbstractFixture implements ContainerAwareInterface
{
    const COUNT = 20;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $workflowDefinitionRepository = $manager->getRepository('OroWorkflowBundle:WorkflowDefinition');

        $firstDefinition = $workflowDefinitionRepository->find(LoadWorkflowDefinitions::FIRST);
        $secondDefinition = $workflowDefinitionRepository->find(LoadWorkflowDefinitions::SECOND);

        if ($firstDefinition) {
            $this->generateEntities($manager, $firstDefinition);
        }

        if ($secondDefinition) {
            $this->generateEntities($manager, $secondDefinition);
        }
    }

    protected function generateEntities(ObjectManager $manager, WorkflowDefinition $definition)
    {
        if (!$definition->getStartStep()) {
            return;
        }

        // load entities
        /** @var WorkflowAwareEntity[] $entities */
        $entities = array();
        for ($i = 1; $i <= self::COUNT; $i++) {
            $entity = new WorkflowAwareEntity();
            $entity->setName($definition->getName() . '_entity_' . $i);
            $entities[] = $entity;
            $manager->persist($entity);
        }
        $manager->flush();

        // create workflow item manually (to make it faster)
        foreach ($entities as $entity) {
            $workflowItem = new WorkflowItem();
            $workflowItem->setDefinition($definition)
                ->setWorkflowName($definition->getName())
                ->setEntity($entity)
                ->setEntityId($entity->getId())
                ->setCurrentStep($definition->getStartStep());
            $manager->persist($workflowItem);

            $entity->setWorkflowItem($workflowItem)
                ->setWorkflowStep($workflowItem->getCurrentStep());
        }
        $manager->flush();
    }
}

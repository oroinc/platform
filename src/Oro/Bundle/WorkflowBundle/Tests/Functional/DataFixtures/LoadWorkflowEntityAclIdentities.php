<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;

class LoadWorkflowEntityAclIdentities extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createIdentity($manager, 'test_active_flow1', 'name', 60);
        $this->createIdentity($manager, 'test_active_flow2', 'name', 80);

        $manager->flush();
    }

    private function createIdentity(ObjectManager $manager, string $workflowName, string $attribute, int $shift): void
    {
        $acl = $this->getReference($workflowName . '_' . $attribute);

        for ($i = 1; $i <= LoadWorkflowAwareEntities::COUNT; $i++) {
            $entity = $this->getReference('workflow_aware_entity.' . $i);
            $item = $this->getReference($workflowName . '_item.' . ($i + $shift));

            $identity = new WorkflowEntityAclIdentity();
            $identity->setAcl($acl)
                ->setEntityClass(WorkflowAwareEntity::class)
                ->setEntityId($entity->getId())
                ->setWorkflowItem($item);

            $manager->persist($identity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadWorkflowEntityAcls::class];
    }
}

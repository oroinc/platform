<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;

class LoadWorkflowEntityAcls extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createAcl($manager, 'test_active_flow1', 'name');
        $this->createAcl($manager, 'test_active_flow2', 'name');

        $manager->flush();
    }

    private function createAcl(ObjectManager $manager, string $workflowName, string $attribute): void
    {
        /** @var WorkflowDefinition $workflow */
        $workflow = $manager->getRepository(WorkflowDefinition::class)
            ->find($workflowName);

        $acl = new WorkflowEntityAcl();
        $acl->setDefinition($workflow)
            ->setAttribute($attribute)
            ->setStep($workflow->getSteps()->first())
            ->setEntityClass(WorkflowAwareEntity::class)
            ->setDeletable(false)
            ->setUpdatable(false);

        $manager->persist($acl);

        $this->addReference($workflowName . '_' . $attribute, $acl);
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadWorkflowAwareEntities::class];
    }
}

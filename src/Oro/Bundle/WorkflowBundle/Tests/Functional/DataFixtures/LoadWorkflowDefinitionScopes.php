<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class LoadWorkflowDefinitionScopes extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadWorkflowDefinitions::class, LoadTestActivitiesForScopes::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addScopesForWorkflow(
            $manager,
            LoadWorkflowDefinitions::WITH_GROUPS1,
            [
                $this->getReference(LoadTestActivitiesForScopes::TEST_ACTIVITY_1),
                $this->getReference(LoadTestActivitiesForScopes::TEST_ACTIVITY_2)
            ]
        );
        $this->addScopesForWorkflow(
            $manager,
            LoadWorkflowDefinitions::WITH_GROUPS2,
            [
                $this->getReference(LoadTestActivitiesForScopes::TEST_ACTIVITY_1),
                $this->getReference(LoadTestActivitiesForScopes::TEST_ACTIVITY_3)
            ]
        );

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $workflowName
     * @param array|TestActivity[] $testActivities
     */
    protected function addScopesForWorkflow(ObjectManager $manager, $workflowName, array $testActivities)
    {
        /** @var WorkflowDefinition $definition */
        $definition = $this->getReference('workflow.' . $workflowName);

        foreach ($testActivities as $testActivity) {
            $scope = new Scope();
            $scope->setTestActivity($testActivity);

            $manager->persist($scope);

            $definition->addScope($scope);
        }
    }
}

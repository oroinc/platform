<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class LoadWorkflowDefinitions extends AbstractFixture implements ContainerAwareInterface
{
    public const NO_START_STEP    = 'test_flow';
    public const WITH_START_STEP  = 'test_start_step_flow';
    public const WITH_INIT_OPTION = 'test_start_init_option';
    public const WITH_DATAGRIDS = 'test_flow_datagrids';
    public const START_TRANSITION = 'start_transition';
    public const MULTISTEP_START_TRANSITION = 'starting_point_transition';
    public const MULTISTEP = 'test_multistep_flow';
    public const WITH_GROUPS1 = 'test_groups_flow1';
    public const WITH_GROUPS2 = 'test_groups_flow2';
    public const START_FROM_ENTITY_TRANSITION = 'start_transition_from_entities';
    public const START_FROM_ROUTE_TRANSITION = 'start_transition_from_routes';
    public const START_FROM_ROUTE_TRANSITION_WITH_FORM = 'start_transition_from_routes_with_form';

    /**
     * @var ContainerInterface
     */
    protected $container;

    #[\Override]
    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $hasDefinitions = false;

        $listConfiguration = $this->container->get('oro_workflow.configuration.config.workflow_list');
        $configurationBuilder = $this->container->get('oro_workflow.configuration.builder.workflow_definition');

        $workflowConfiguration = $this->getWorkflowConfiguration();
        $workflowConfiguration = $listConfiguration->processConfiguration($workflowConfiguration);
        $workflowDefinitions = $configurationBuilder->buildFromConfiguration($workflowConfiguration);

        foreach ($workflowDefinitions as $workflowDefinition) {
            if ($manager->getRepository(WorkflowDefinition::class)->find($workflowDefinition->getName())) {
                continue;
            }

            if (self::WITH_START_STEP === $workflowDefinition->getName()) {
                $workflowDefinition->setSystem(true);
            }

            $manager->persist($workflowDefinition);
            $this->addReference('workflow.' . $workflowDefinition->getName(), $workflowDefinition);
            $hasDefinitions = true;
        }

        if ($hasDefinitions) {
            $manager->flush();
        }
    }

    /**
     * @return array
     */
    protected function getWorkflowConfiguration()
    {
        return Yaml::parse(file_get_contents(__DIR__ . '/config/oro/workflows.yml')) ?: [];
    }
}

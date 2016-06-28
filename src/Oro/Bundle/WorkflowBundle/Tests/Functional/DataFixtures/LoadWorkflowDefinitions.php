<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadWorkflowDefinitions extends AbstractFixture implements ContainerAwareInterface
{
    const NO_START_STEP    = 'test_flow';
    const WITH_START_STEP  = 'test_start_step_flow';
    const START_TRANSITION = 'start_transition';
    const MULTISTEP_START_TRANSITION = 'starting_point_transition';
    const MULTISTEP = 'test_multistep_flow';

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
        $hasDefinitions = false;

        $listConfiguration = $this->container->get('oro_workflow.configuration.config.workflow_list');
        $configurationBuilder = $this->container->get('oro_workflow.configuration.builder.workflow_definition');

        $workflowConfiguration = Yaml::parse(file_get_contents(__DIR__ . '/config/workflows.yml')) ? : [];
        $workflowConfiguration = $listConfiguration->processConfiguration($workflowConfiguration);
        $workflowDefinitions = $configurationBuilder->buildFromConfiguration($workflowConfiguration);

        foreach ($workflowDefinitions as $workflowDefinition) {
            if ($manager->getRepository('OroWorkflowBundle:WorkflowDefinition')->find($workflowDefinition->getName())) {
                continue;
            }

            $manager->persist($workflowDefinition);
            $hasDefinitions = true;
        }

        if ($hasDefinitions) {
            $manager->flush();
        }
    }
}

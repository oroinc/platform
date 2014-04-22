<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Parser;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class LoadWorkflowDefinitions extends AbstractFixture implements ContainerAwareInterface
{
    const FIRST  = 'test_flow';
    const SECOND = 'test_start_step_flow';

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
        $yaml = new Parser();

        $workflows = $yaml->parse(file_get_contents(__DIR__ . '/config/workflows.yml'));
        $configurationBuilder = $this->container->get('oro_workflow.configuration.builder.workflow_definition');
        $workflowDefinitions = $configurationBuilder->buildFromConfiguration($workflows);

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

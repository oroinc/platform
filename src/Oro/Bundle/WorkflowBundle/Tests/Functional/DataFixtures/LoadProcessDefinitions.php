<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class LoadProcessDefinitions extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
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
        $processConfiguration = $this->getProcessConfiguration();

        $processConfigurator = $this->container->get('oro_workflow.process.configurator');
        $processConfigurator->configureProcesses($processConfiguration);

        // update triggers cache
        $this->container->get('oro_workflow.cache.process_trigger')->build();
    }

    /**
     * @return array
     */
    protected function getProcessConfiguration()
    {
        $configuration = Yaml::parse(file_get_contents(__DIR__ . '/config/oro/processes.yml')) ?: [];
        $configuration = $configuration['processes'] ?: [];

        return $configuration;
    }
}

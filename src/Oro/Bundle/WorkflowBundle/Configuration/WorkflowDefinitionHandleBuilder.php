<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionHandleBuilder extends AbstractConfigurationBuilder
{
    /**
     * @var ConfigurationHandlerInterface[]
     */
    protected $handlers = [];

    /**
     * @var WorkflowConfiguration
     */
    protected $configuration;

    /**
     * @var WorkflowDefinitionConfigurationBuilder
     */
    protected $configurationBuilder;

    /**
     * @param WorkflowConfiguration $configuration
     * @param WorkflowDefinitionConfigurationBuilder $configurationBuilder
     */
    public function __construct(
        WorkflowConfiguration $configuration,
        WorkflowDefinitionConfigurationBuilder $configurationBuilder
    ) {
        $this->configuration = $configuration;
        $this->configurationBuilder = $configurationBuilder;
    }

    /**
     * @param array $configuration
     * @return WorkflowDefinition
     */
    public function buildFromRawConfiguration(array $configuration)
    {
        foreach ($this->handlers as $handler) {
            $configuration = $handler->handle($configuration);
        }

        $configuration = $this->configuration->processConfiguration($configuration);

        $this->assertConfigurationOptions($configuration, array('name'));
        $name = $this->getConfigurationOption($configuration, 'name');

        return $this->configurationBuilder->buildOneFromConfiguration($name, $configuration);
    }

    /**
     * @param ConfigurationHandlerInterface $handler
     */
    public function addHandler(ConfigurationHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }
}

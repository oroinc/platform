<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionHandleBuilder extends AbstractConfigurationBuilder
{
    /**
     * @var ConfigurationHandlerInterface
     */
    protected $handler;

    /**
     * @var WorkflowConfiguration
     */
    protected $configuration;

    /**
     * @var WorkflowDefinitionConfigurationBuilder
     */
    protected $configurationBuilder;

    /**
     * @param ConfigurationHandlerInterface $handler
     * @param WorkflowConfiguration $configuration
     * @param WorkflowDefinitionConfigurationBuilder $configurationBuilder
     */
    public function __construct(
        ConfigurationHandlerInterface $handler,
        WorkflowConfiguration $configuration,
        WorkflowDefinitionConfigurationBuilder $configurationBuilder
    ) {
        $this->handler = $handler;
        $this->configuration = $configuration;
        $this->configurationBuilder = $configurationBuilder;
    }

    /**
     * @param array $configuration
     * @return WorkflowDefinition
     */
    public function buildFromRawConfiguration(array $configuration)
    {
        $configuration = $this->handler->handle($configuration);
        $configuration = $this->configuration->processConfiguration($configuration);

        $this->assertConfigurationOptions($configuration, array('name'));
        $name = $this->getConfigurationOption($configuration, 'name');

        return $this->configurationBuilder->buildOneFromConfiguration($name, $configuration);
    }
}

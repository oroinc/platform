<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\MissedRequiredOptionException;

/**
 * The builder for workflow definitions that uses registered handlers to pre-process workflow configuration.
 */
class WorkflowDefinitionHandleBuilder
{
    /** @var WorkflowConfiguration */
    private $configuration;

    /** @var WorkflowDefinitionConfigurationBuilder */
    private $configurationBuilder;

    /** @var iterable|ConfigurationHandlerInterface[] */
    private $handlers;

    /**
     * @param WorkflowConfiguration                    $configuration
     * @param WorkflowDefinitionConfigurationBuilder   $configurationBuilder
     * @param iterable|ConfigurationHandlerInterface[] $handlers
     */
    public function __construct(
        WorkflowConfiguration $configuration,
        WorkflowDefinitionConfigurationBuilder $configurationBuilder,
        iterable $handlers
    ) {
        $this->configuration = $configuration;
        $this->configurationBuilder = $configurationBuilder;
        $this->handlers = $handlers;
    }

    /**
     * @param array $configuration
     *
     * @return WorkflowDefinition
     */
    public function buildFromRawConfiguration(array $configuration)
    {
        foreach ($this->handlers as $handler) {
            $configuration = $handler->handle($configuration);
        }

        $configuration = $this->configuration->processConfiguration($configuration);

        if (!isset($configuration['name'])) {
            throw new MissedRequiredOptionException('The "name" configuration option is required.');
        }

        return $this->configurationBuilder->buildOneFromConfiguration($configuration['name'], $configuration);
    }
}

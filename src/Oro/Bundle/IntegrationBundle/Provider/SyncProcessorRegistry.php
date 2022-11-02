<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;

/**
 * Registry for integration processors
 */
class SyncProcessorRegistry
{
    /**
     * @var SyncProcessorInterface[]
     */
    protected $processors = [];

    /**
     * @var SyncProcessorInterface
     */
    protected $defaultProcessor;

    /**
     * @param string $integrationName
     * @param SyncProcessorInterface $processor
     * @return SyncProcessorRegistry
     */
    public function addProcessor($integrationName, SyncProcessorInterface $processor)
    {
        $this->processors[$integrationName] = $processor;

        return $this;
    }

    /**
     * @param Channel $integration
     * @return SyncProcessorInterface
     */
    public function getProcessorForIntegration(Channel $integration)
    {
        if ($this->hasProcessorForIntegration($integration)) {
            return $this->processors[$integration->getType()];
        }

        if (!$this->defaultProcessor) {
            throw new InvalidConfigurationException('Default sync processor was not set');
        }

        return $this->defaultProcessor;
    }

    /**
     * @param Channel $integration
     * @return bool
     */
    public function hasProcessorForIntegration(Channel $integration)
    {
        return array_key_exists($integration->getType(), $this->processors);
    }

    /**
     * @return SyncProcessorInterface
     */
    public function getDefaultProcessor()
    {
        return $this->defaultProcessor;
    }

    /**
     * @param SyncProcessorInterface $defaultProcessor
     * @return SyncProcessorRegistry
     */
    public function setDefaultProcessor(SyncProcessorInterface $defaultProcessor)
    {
        $this->defaultProcessor = $defaultProcessor;

        return $this;
    }
}

<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;

class SyncProcessorRegistry
{
    /**
     * @var AbstractSyncProcessor[]
     */
    protected $processors = [];

    /**
     * @var AbstractSyncProcessor
     */
    protected $defaultProcessor;

    /**
     * @param string $integrationName
     * @param AbstractSyncProcessor $processor
     * @return SyncProcessorRegistry
     */
    public function addProcessor($integrationName, AbstractSyncProcessor $processor)
    {
        $this->processors[$integrationName] = $processor;

        return $this;
    }

    /**
     * @param Channel $integration
     * @return AbstractSyncProcessor
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
     * @return AbstractSyncProcessor
     */
    public function getDefaultProcessor()
    {
        return $this->defaultProcessor;
    }

    /**
     * @param AbstractSyncProcessor $defaultProcessor
     * @return SyncProcessorRegistry
     */
    public function setDefaultProcessor(AbstractSyncProcessor $defaultProcessor)
    {
        $this->defaultProcessor = $defaultProcessor;

        return $this;
    }
}

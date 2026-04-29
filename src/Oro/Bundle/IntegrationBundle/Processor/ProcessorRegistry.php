<?php

namespace Oro\Bundle\IntegrationBundle\Processor;

use Oro\Bundle\IntegrationBundle\Exception\UnknownWebhookProcessorException;
use Psr\Container\ContainerInterface;

/**
 * Handles the registration and retrieval of processors within a registry.
 */
class ProcessorRegistry
{
    public function __construct(
        private ContainerInterface $locator
    ) {
    }

    public function getProcessor(string $name): WebhookProcessorInterface
    {
        if ($this->locator->has($name)) {
            return $this->locator->get($name);
        }

        throw new UnknownWebhookProcessorException(sprintf('Processor "%s" is not registered.', $name));
    }
}

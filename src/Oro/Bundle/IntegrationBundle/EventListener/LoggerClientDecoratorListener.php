<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Oro\Bundle\IntegrationBundle\Event\ClientCreatedAfterEvent;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator\LoggerClientDecorator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class LoggerClientDecoratorListener extends AbstractClientDecoratorListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const CONFIG_KEY = 'logger';

    protected $defaultConfigurationParameters = [
        'enabled' => true,
    ];

    #[\Override]
    protected function attachDecorator(ClientCreatedAfterEvent $event, array $configuration)
    {
        $client = new LoggerClientDecorator(
            $event->getClient(),
            $this->logger
        );
        $event->setClient($client);
    }

    #[\Override]
    protected function getDefaultConfigurationParameters()
    {
        return $this->defaultConfigurationParameters;
    }

    #[\Override]
    protected function getConfigurationKey()
    {
        return self::CONFIG_KEY;
    }

    #[\Override]
    protected function isEnabled(array $configuration)
    {
        return (bool) $configuration['enabled'];
    }

    #[\Override]
    protected function isApplicable(ClientCreatedAfterEvent $event)
    {
        return true;
    }
}

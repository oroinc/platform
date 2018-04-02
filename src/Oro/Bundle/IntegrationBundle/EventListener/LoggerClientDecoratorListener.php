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

    /**
     * {@inheritdoc}
     */
    protected function attachDecorator(ClientCreatedAfterEvent $event, array $configuration)
    {
        $client = new LoggerClientDecorator(
            $event->getClient(),
            $this->logger
        );
        $event->setClient($client);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfigurationParameters()
    {
        return $this->defaultConfigurationParameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigurationKey()
    {
        return self::CONFIG_KEY;
    }

    /**
     * {@inheritdoc}
     */
    protected function isEnabled(array $configuration)
    {
        return (bool) $configuration['enabled'];
    }

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(ClientCreatedAfterEvent $event)
    {
        return true;
    }
}

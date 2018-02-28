<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Oro\Bundle\IntegrationBundle\Event\ClientCreatedAfterEvent;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator\MultiAttemptsClientDecorator;
use Oro\Bundle\IntegrationBundle\Utils\MultiAttemptsConfigTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class MultiAttemptsClientDecoratorListener extends AbstractClientDecoratorListener implements LoggerAwareInterface
{
    use LoggerAwareTrait, MultiAttemptsConfigTrait;

    /**
     * {@inheritdoc}
     */
    protected function attachDecorator(ClientCreatedAfterEvent $event, array $configuration)
    {
        $client = new MultiAttemptsClientDecorator(
            $event->getClient(),
            $this->logger,
            $this->getSleepBetweenAttemptsParameter($configuration)
        );
        $event->setClient($client);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfigurationParameters()
    {
        return $this->multiAttemptsDefaultConfigurationParameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigurationKey()
    {
        return self::$multiAttemptsConfigKey;
    }

    /**
     * {@inheritdoc}
     */
    protected function isEnabled(array $configuration)
    {
        return $this->getMultiAttemptsEnabledParameter($configuration);
    }

    /**
     * {@inheritdoc}
     */
    protected function isApplicable(ClientCreatedAfterEvent $event)
    {
        return true;
    }
}

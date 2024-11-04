<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Oro\Bundle\IntegrationBundle\Event\ClientCreatedAfterEvent;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator\MultiAttemptsClientDecorator;
use Oro\Bundle\IntegrationBundle\Utils\MultiAttemptsConfigTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Decorates REST client to retry requests in case of connection problems
 */
class MultiAttemptsClientDecoratorListener extends AbstractClientDecoratorListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use MultiAttemptsConfigTrait;

    #[\Override]
    protected function attachDecorator(ClientCreatedAfterEvent $event, array $configuration)
    {
        $client = new MultiAttemptsClientDecorator(
            $event->getClient(),
            $this->logger,
            $this->getSleepBetweenAttemptsParameter($configuration)
        );
        $event->setClient($client);
    }

    #[\Override]
    protected function getDefaultConfigurationParameters()
    {
        return $this->multiAttemptsDefaultConfigurationParameters;
    }

    #[\Override]
    protected function getConfigurationKey()
    {
        return self::$multiAttemptsConfigKey;
    }

    #[\Override]
    protected function isEnabled(array $configuration)
    {
        return $this->getMultiAttemptsEnabledParameter($configuration);
    }

    #[\Override]
    protected function isApplicable(ClientCreatedAfterEvent $event)
    {
        return true;
    }
}

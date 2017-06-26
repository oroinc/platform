<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\IntegrationBundle\Event\ClientCreatedAfterEvent;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\RestTransportSettingsInterface;

/**
 * Class EventDispatchableRestClientFactory is extending basic factory functionality
 * with event which can be used to decorate REST client or replace it
 *
 * @see ClientCreatedAfterEvent
 */
class EventDispatchableRestClientFactory implements FactoryInterface
{
    /**
     * @var RestClientFactoryInterface
     */
    protected $legacyClientFactory;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @param RestClientFactoryInterface $clientFactory
     * @param EventDispatcher            $dispatcher
     */
    public function __construct(RestClientFactoryInterface $clientFactory, EventDispatcher $dispatcher)
    {
        $this->legacyClientFactory = $clientFactory;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientInstance(RestTransportSettingsInterface $transportEntity)
    {
        $client = $this->legacyClientFactory->createRestClient(
            $transportEntity->getBaseUrl(),
            $transportEntity->getOptions()
        );

        $clientCreatedAfterEvent = new ClientCreatedAfterEvent(
            $client,
            $transportEntity
        );

        $this->dispatcher->dispatch(ClientCreatedAfterEvent::NAME, $clientCreatedAfterEvent);

        return $clientCreatedAfterEvent->getClient();
    }
}

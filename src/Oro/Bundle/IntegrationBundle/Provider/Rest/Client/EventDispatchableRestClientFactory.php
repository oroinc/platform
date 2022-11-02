<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client;

use Oro\Bundle\IntegrationBundle\Event\ClientCreatedAfterEvent;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\RestTransportSettingsInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(RestClientFactoryInterface $clientFactory, EventDispatcherInterface $dispatcher)
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

        $this->dispatcher->dispatch($clientCreatedAfterEvent, ClientCreatedAfterEvent::NAME);

        return $clientCreatedAfterEvent->getClient();
    }
}

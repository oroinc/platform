<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\RestTransportSettingsInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * ClientCreatedAfterEvent is an event which called when new client is created
 *
 * Use it if you want decorate or replace client in case of not standard behavior
 */
class ClientCreatedAfterEvent extends Event
{
    const NAME = 'oro_integration.client_created_after';

    /**
     * @var RestClientInterface
     */
    protected $client;

    /**
     * @var RestTransportSettingsInterface
     */
    protected $transportEntity;

    /**
     * @var ParameterBag
     */
    protected $additionalParameterBag;

    /**
     * @param RestClientInterface            $client
     * @param RestTransportSettingsInterface $transportEntity
     */
    public function __construct(
        RestClientInterface $client,
        RestTransportSettingsInterface $transportEntity
    ) {
        $this->client = $client;
        $this->transportEntity = $transportEntity;
    }

    /**
     * @return RestClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param RestClientInterface $client
     */
    public function setClient(RestClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return ParameterBag
     */
    public function getAdditionalParameterBag()
    {
        if (!$this->additionalParameterBag) {
            $this->additionalParameterBag = new ParameterBag($this->transportEntity->getOptions());
        }

        return $this->additionalParameterBag;
    }

    /**
     * @return RestTransportSettingsInterface
     */
    public function getTransportEntity()
    {
        return $this->transportEntity;
    }
}

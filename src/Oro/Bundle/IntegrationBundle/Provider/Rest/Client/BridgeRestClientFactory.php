<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;

abstract class BridgeRestClientFactory
{
    /**
     * @var RestClientFactoryInterface
     */
    protected $clientFactory;

    /**
     * @param RestClientFactoryInterface $clientFactory
     */
    public function __construct(RestClientFactoryInterface $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * @param Transport $transportEntity
     *
     * @return RestClientInterface
     */
    public function createRestClient(Transport $transportEntity)
    {
        $settings = $transportEntity->getSettingsBag();
        $baseUrl = $this->getClientBaseUrl($settings);
        $clientOptions = $this->getClientOptions($settings);
        return $this->clientFactory->createRestClient($baseUrl, $clientOptions);
    }

    /**
     * Get REST client base url
     *
     * @param ParameterBag $parameterBag
     *
     * @return string
     *
     * @throws InvalidConfigurationException
     */
    abstract protected function getClientBaseUrl(ParameterBag $parameterBag);

    /**
     * Get REST client options
     *
     * @param ParameterBag $parameterBag
     *
     * @return array
     *
     * @throws InvalidConfigurationException
     */
    abstract protected function getClientOptions(ParameterBag $parameterBag);
}

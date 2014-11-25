<?php
namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Transport;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

abstract class AbstractRestTransport implements TransportInterface
{
    /**
     * @var ParameterBag
     */
    protected $settings;

    /**
     * @var RestClientInterface
     */
    protected $client;

    /**
     * @var RestClientFactoryInterface
     */
    protected $clientFactory;

    /**
     * @param RestClientFactoryInterface $clientFactory
     */
    public function setRestClientFactory(RestClientFactoryInterface $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
        $this->settings = $transportEntity->getSettingsBag();
        $this->client = $this->createRestClient($transportEntity);
    }

    /**
     * Create REST client
     *
     * @param Transport $transportEntity
     * @return RestClientInterface
     * @throws InvalidConfigurationException
     */
    protected function createRestClient(Transport $transportEntity)
    {
        $settings = $transportEntity->getSettingsBag();
        $baseUrl = $this->getClientBaseUrl($settings);
        $clientOptions = $this->getClientOptions($settings);
        return $this->getClientFactory()->createRestClient($baseUrl, $clientOptions);
    }

    /**
     * Get REST client base url
     *
     * @param ParameterBag $parameterBag
     * @return string
     * @throws InvalidConfigurationException
     */
    abstract protected function getClientBaseUrl(ParameterBag $parameterBag);

    /**
     * Get REST client options
     *
     * @param ParameterBag $parameterBag
     * @return array
     * @throws InvalidConfigurationException
     */
    abstract protected function getClientOptions(ParameterBag $parameterBag);

    /**
     * Get REST client
     *
     * @return RestClientInterface
     * @throws InvalidConfigurationException
     */
    public function getClient()
    {
        if (!$this->client) {
            throw new InvalidConfigurationException("REST Transport isn't configured properly.");
        }
        return $this->client;
    }

    /**
     * Get REST client factory
     *
     * @return RestClientFactoryInterface
     * @throws InvalidConfigurationException
     */
    protected function getClientFactory()
    {
        if (!$this->clientFactory) {
            throw new InvalidConfigurationException("REST Transport isn't configured properly.");
        }
        return $this->clientFactory;
    }
}

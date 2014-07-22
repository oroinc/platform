<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Guzzle\Http\Url;
use Guzzle\Parser\ParserRegistry;

use FOS\Rest\Util\Codes;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Exception\SoapConnectionException;

/**
 * @package Oro\Bundle\IntegrationBundle
 */
abstract class SOAPTransport implements TransportInterface
{
    const ATTEMPTS = 5;
    const SLEEP_BETWEEN_QUERIES = 1000000;

    /** @var ParameterBag */
    protected $settings;

    /** @var \SoapClient */
    protected $client;

    /** @var int */
    protected $repeated;

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
        $this->resetRepetition();
        $this->settings = $transportEntity->getSettingsBag();
        $wsdlUrl        = $this->settings->get('wsdl_url');

        if (!$wsdlUrl) {
            throw new InvalidConfigurationException("SOAP Transport require 'wsdl_url' option to be defined.");
        }

        $this->client = $this->getSoapClient($wsdlUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function call($action, $params = [])
    {
        if (!$this->client) {
            throw new InvalidConfigurationException("SOAP Transport does not configured properly.");
        }

        try {
            $result = $this->client->__soapCall($action, $params);
        } catch (\Exception $e) {

            if ($this->isRepetitionNecessary()) {
                usleep(self::SLEEP_BETWEEN_QUERIES);
                $this->increaseRepeated();
                $result = $this->call($action, $params);
            } else {
                $this->resetRepetition();

                throw SoapConnectionException::createFromResponse(
                    $e,
                    $this->getLastResponse(),
                    $this->getLastRequest(),
                    $this->client->__getLastResponseHeaders()
                );
            }
        }

        $this->resetRepetition();

        return $result;
    }

    /**
     * @return string last SOAP response
     */
    public function getLastResponse()
    {
        return $this->client->__getLastResponse();
    }

    /**
     * @return string last SOAP request
     */
    public function getLastRequest()
    {
        return $this->client->__getLastRequest();
    }

    /**
     * Clone
     */
    public function __clone()
    {
        $this->client = null;
    }

    /**
     * Does not allow to serialize
     * It may cause serialization error on SoapClient
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }

    /**
     * @param string $wsdlUrl
     *
     * @return \SoapClient
     */
    protected function getSoapClient($wsdlUrl)
    {
        $options          = [];
        $options['trace'] = true;
        $urlParts         = parse_url($wsdlUrl);

        if (isset($urlParts['user'], $urlParts['pass'])) {
            $options['login']    = $urlParts['user'];
            $options['password'] = $urlParts['pass'];
            unset($urlParts['user'], $urlParts['pass']);
        }
        $wsdlUrl = Url::buildUrl($urlParts);

        return new \SoapClient($wsdlUrl, $options);
    }

    /**
     * Reset repeat count to 0
     */
    protected function resetRepetition()
    {
        $this->repeated = 0;
    }

    /**
     * Increment repeat count on one
     */
    protected function increaseRepeated()
    {
        ++$this->repeated;
    }

    /**
     * Get last request headers as array
     *
     * @return array
     */
    protected function getLastCallHeaders()
    {
        return ParserRegistry::getInstance()->getParser('message')
            ->parseResponse($this->client->__getLastResponseHeaders());
    }

    /**
     * @param array $headers
     *
     * @return bool
     */
    protected function isResultOk(array $headers = [])
    {
        if (!empty($headers['code']) && Codes::HTTP_OK === (int)$headers['code']) {
                return true;
        }
        return false;
    }

    /**
     * @param array $headers
     *
     * @return int
     */
    protected function getHttpStatusCode(array $headers = [])
    {
        return (!empty($headers['code'])) ? (int)$headers['code'] : 0;
    }

    /**
     * @return bool
     */
    protected function isRepetitionNecessary()
    {
        if ($this->repeated < self::ATTEMPTS) {

            $headers = $this->getLastCallHeaders();



            if (!$this->isResultOk($headers)) {
                $statusCode = $this->getHttpStatusCode($headers);

                if ($statusCode === Codes::HTTP_BAD_GATEWAY
                    || $statusCode === Codes::HTTP_SERVICE_UNAVAILABLE
                    || $statusCode === Codes::HTTP_GATEWAY_TIMEOUT
                ) {
                    return true;
                }
            }
        }
        return false;
    }
}

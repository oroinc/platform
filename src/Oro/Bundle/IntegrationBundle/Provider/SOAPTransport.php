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
    const ATTEMPTS              = 5;
    const SLEEP_BETWEEN_ATTEMPT = 1;

    /** @var ParameterBag */
    protected $settings;

    /** @var \SoapClient */
    protected $client;

    /** @var int */
    protected $attempted;

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
        $this->resetAttemptCount();
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
        ini_set('default_socket_timeout', 1);

        if (!$this->client) {
            throw new InvalidConfigurationException("SOAP Transport does not configured properly.");
        }
        try {
            $result = $this->client->__soapCall($action, $params);
            var_dump($result);
        } catch (\Exception $e) {

            if ($this->isAttemptNecessary()) {

                sleep(self::SLEEP_BETWEEN_ATTEMPT);
                $this->attempt();
                $result = $this->call($action, $params);
            } else {
                $this->resetAttemptCount();

                throw SoapConnectionException::createFromResponse(
                    $this->getLastResponse(),
                    $e,
                    $this->getLastRequest(),
                    $this->client->__getLastResponseHeaders()
                );
            }
        }

        $this->resetAttemptCount();

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
    protected function resetAttemptCount()
    {
        $this->attempted = 0;
    }

    /**
     * Increment repeat count on one
     */
    protected function attempt()
    {
        ++$this->attempted;
    }

    /**
     * @return bool
     */
    protected function shouldAttempt()
    {
        return $this->attempted < self::ATTEMPTS;
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
    protected function isAttemptNecessary()
    {
        if ($this->shouldAttempt()) {
            $headers = $this->getLastCallHeaders();

            if (!empty($headers) && !$this->isResultOk($headers)) {
                $statusCode = $this->getHttpStatusCode($headers);

                if (in_array($statusCode, $this->getHttpStatusesForAttempt())) {
                    return true;
                }
            } elseif (empty($headers)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    protected function getHttpStatusesForAttempt()
    {
        return [Codes::HTTP_BAD_GATEWAY, Codes::HTTP_SERVICE_UNAVAILABLE, Codes::HTTP_GATEWAY_TIMEOUT];
    }
}

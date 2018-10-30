<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use FOS\RestBundle\Util\Codes;
use Guzzle\Http\Url;
use Guzzle\Parser\ParserRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Exception\SoapConnectionException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Base class for interacting with 3rd party system using SOAP
 */
abstract class SOAPTransport implements TransportInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ParameterBag */
    protected $settings;

    /** @var NonPrintableCharsSanitizedSoapClient */
    protected $client;

    /** @var int */
    protected $attempted;

    /** @var bool */
    protected $multipleAttemptsEnabled = true;

    /** @var array */
    protected $sleepBetweenAttempt = [5, 10, 20, 40];

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
        $this->resetAttemptCount();
        $this->settings = $transportEntity->getSettingsBag();
        $wsdlUrl = $this->settings->get('wsdl_url');

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
            $isUnknownMethod = strpos($e->getMessage(), 'is not a valid method') !== false
                || $e->getMessage() === sprintf("Procedure '%s' not present", $action);

            if (!$isUnknownMethod && $this->isAttemptNecessary()) {
                $result = $this->makeNewAttempt($action, $params);
            } else {
                $this->resetAttemptCount();

                throw SoapConnectionException::createFromResponse(
                    $this->getLastResponse(),
                    $e,
                    $this->getLastRequest(),
                    $this->getLastResponseHeaders()
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
     * @param boolean $multipleAttemptsEnabled
     */
    public function setMultipleAttemptsEnabled($multipleAttemptsEnabled)
    {
        $this->multipleAttemptsEnabled = $multipleAttemptsEnabled;
    }

    /**
     * @param array $sleepBetweenAttempt
     */
    public function setSleepBetweenAttempt(array $sleepBetweenAttempt)
    {
        $this->sleepBetweenAttempt = $sleepBetweenAttempt;
    }

    /**
     * @param string $wsdlUrl
     *
     * @param array $options
     *
     * @return NonPrintableCharsSanitizedSoapClient
     */
    protected function getSoapClient($wsdlUrl, array $options = [])
    {
        $options['trace'] = true;
        $urlParts = parse_url($wsdlUrl);

        if (isset($urlParts['user'], $urlParts['pass'])) {
            $options['login'] = $urlParts['user'];
            $options['password'] = $urlParts['pass'];
            unset($urlParts['user'], $urlParts['pass']);
        }
        $wsdlUrl = Url::buildUrl($urlParts);

        return new NonPrintableCharsSanitizedSoapClient($wsdlUrl, $options);
    }

    /**
     * Reset count attempt into 0
     */
    protected function resetAttemptCount()
    {
        $this->attempted = 0;
    }

    /**
     * Make new attempt
     */
    protected function makeNewAttempt($action, $params)
    {
        $this->logAttempt();
        sleep($this->getSleepBetweenAttempt());
        ++$this->attempted;

        return $this->call($action, $params);
    }

    /**
     * Get last request headers as array
     *
     * @return array
     */
    protected function getLastResponseHeaders()
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
        if (Codes::HTTP_OK === $this->getHttpStatusCode($headers)) {
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
        $couldPerform = $this->multipleAttemptsEnabled && ($this->attempted < count($this->sleepBetweenAttempt) - 1);

        if ($couldPerform) {
            $headers = $this->getLastResponseHeaders();
            $response = $this->getLastResponse();

            if (!empty($headers) && !$this->isResultOk($headers)) {
                $statusCode = $this->getHttpStatusCode($headers);

                if (in_array($statusCode, $this->getHttpStatusesForAttempt())) {
                    return true;
                }
            } elseif (!empty($headers) && $this->isResultOk($headers) && strpos($response, '<?xml') !== 0) {
                return true;
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
        return [
            Codes::HTTP_BAD_GATEWAY,
            Codes::HTTP_SERVICE_UNAVAILABLE,
            Codes::HTTP_GATEWAY_TIMEOUT,
        ];
    }

    /**
     * Returns the current item by $attempted or the last of them
     *
     * @return int
     */
    protected function getSleepBetweenAttempt()
    {
        if (!empty($this->sleepBetweenAttempt[$this->attempted])) {
            return (int)$this->sleepBetweenAttempt[$this->attempted];
        }

        return (int)end($this->sleepBetweenAttempt);
    }

    /**
     * Log attempt
     */
    protected function logAttempt()
    {
        if (!empty($this->logger)) {
            $this->logger->warning(
                '[Warning] Attempt number ' . ($this->attempted + 1)
                . ' with ' . $this->getSleepBetweenAttempt() . ' sec delay.'
            );
        }
    }
}

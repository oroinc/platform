<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator;

use FOS\RestBundle\Util\Codes;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Psr\Log\LoggerInterface;

class MultiAttemptsClientDecorator implements RestClientInterface
{
    /**
     * @var int
     */
    protected $attempted = 0;

    /**
     * @var array
     */
    protected $sleepBetweenAttempt;

    /**
     * @var RestClientInterface
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        RestClientInterface $client,
        LoggerInterface $logger,
        array $sleepBetweenAttempt
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->sleepBetweenAttempt = $sleepBetweenAttempt;
    }

    /**
     * @param array $sleepBetweenAttempt
     */
    public function setSleepBetweenAttempt(array $sleepBetweenAttempt)
    {
        $this->sleepBetweenAttempt = $sleepBetweenAttempt;
    }

    /**
     * {@inheritdoc}
     */
    public function get($resource, array $params = array(), array $headers = array(), array $options = array())
    {
        try {
            $response = $this->client->get($resource, $params, $headers, $options);
        } catch (RestException $exception) {
            if ($this->canMakeNewAttempt($exception)) {
                return $this->get($resource, $params, $headers, $options);
            }

            throw $exception;
        }

        $this->resetAttemptsCount();

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function post($resource, $data, array $headers = array(), array $options = array())
    {
        try {
            $response = $this->client->post($resource, $data, $headers, $options);
        } catch (RestException $exception) {
            if ($this->canMakeNewAttempt($exception)) {
                return $this->post($resource, $data, $headers, $options);
            }

            throw $exception;
        }

        $this->resetAttemptsCount();

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($resource, array $headers = array(), array $options = array())
    {
        try {
            $response = $this->client->delete($resource, $headers, $options);
        } catch (RestException $exception) {
            if ($this->canMakeNewAttempt($exception)) {
                return $this->delete($resource, $headers, $options);
            }

            throw $exception;
        }

        $this->resetAttemptsCount();

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function put($resource, $data, array $headers = array(), array $options = array())
    {
        try {
            $response = $this->client->put($resource, $data, $headers, $options);
        } catch (RestException $exception) {
            if ($this->canMakeNewAttempt($exception)) {
                return $this->put($resource, $data, $headers, $options);
            }

            throw $exception;
        }

        $this->resetAttemptsCount();

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getJSON($resource, array $params = array(), array $headers = array(), array $options = array())
    {
        try {
            $response = $this->client->getJSON($resource, $params, $headers, $options);
        } catch (RestException $exception) {
            if ($this->canMakeNewAttempt($exception)) {
                return $this->client->getJSON($resource, $params, $headers, $options);
            }

            throw $exception;
        }

        $this->resetAttemptsCount();

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastResponse()
    {
        $this->client->getLastResponse();
    }

    /**
     * @param RestException $exception
     *
     * @return bool
     */
    protected function canMakeNewAttempt(RestException $exception)
    {
        $response = $exception->getResponse();
        if ($response instanceof RestResponseInterface && $this->isNewAttemptAvailable($response)) {
            $this->nextAttempt();

            return true;
        }

        return false;
    }

    protected function nextAttempt()
    {
        $this->logger->warning(
            sprintf(
                'Attempt number %s with %s sec delay.',
                $this->attempted + 1,
                $this->getSleepBetweenAttempt()
            )
        );

        sleep($this->getSleepBetweenAttempt());
        ++$this->attempted;
    }

    protected function resetAttemptsCount()
    {
        $this->attempted = 0;
    }

    /**
     * @return bool
     */
    protected function isNewAttemptAvailable(RestResponseInterface $response)
    {
        return ($this->attempted < count($this->sleepBetweenAttempt) - 1) &&
            in_array($response->getStatusCode(), $this->getHttpStatusesForAttempt());
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
}

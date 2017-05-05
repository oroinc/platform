<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

class MultiAttemptsClientDecoratorFactory extends AbstractRestClientDecoratorFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var bool */
    protected $multipleAttemptsEnabled = true;

    /** @var array */
    protected $sleepBetweenAttempt = [5, 10, 20, 40];

    /**
     * @return boolean
     */
    public function isMultipleAttemptsEnabled()
    {
        return $this->multipleAttemptsEnabled;
    }

    /**
     * @param boolean $multipleAttemptsEnabled
     */
    public function setMultipleAttemptsEnabled($multipleAttemptsEnabled)
    {
        $this->multipleAttemptsEnabled = (bool) $multipleAttemptsEnabled;
    }

    /**
     * @return array
     */
    public function getSleepBetweenAttempt()
    {
        return $this->sleepBetweenAttempt;
    }

    /**
     * @param array $sleepBetweenAttempt
     */
    public function setSleepBetweenAttempt(array $sleepBetweenAttempt)
    {
        $this->sleepBetweenAttempt = $sleepBetweenAttempt;
    }

    /**
     * @inheritDoc
     */
    public function createRestClient($baseUrl, array $defaultOptions)
    {
        $client = $this->getRestClientFactory()->createRestClient($baseUrl, $defaultOptions);
        return new MultiAttemptsClientDecorator(
            $client,
            $this->logger,
            $this->multipleAttemptsEnabled,
            $this->sleepBetweenAttempt
        );
    }
}

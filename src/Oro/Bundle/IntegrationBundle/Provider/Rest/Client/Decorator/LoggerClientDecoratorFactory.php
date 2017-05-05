<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Decorator;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

class LoggerClientDecoratorFactory extends AbstractRestClientDecoratorFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    /**
     * @inheritDoc
     */
    public function createRestClient($baseUrl, array $defaultOptions)
    {
        $client = $this->getRestClientFactory()->createRestClient($baseUrl, $defaultOptions);
        return new LoggerClientDecorator($client, $this->logger);
    }
}

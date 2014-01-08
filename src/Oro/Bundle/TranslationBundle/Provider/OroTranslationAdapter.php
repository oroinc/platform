<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

class OroTranslationAdapter
{
    /** @var string */
    protected $apiKey;

    /** @var string endpoint URL */
    protected $endpoint;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ApiRequestInterface */
    protected $apiRequest;

    public function __construct(ApiRequestInterface $apiRequest, $endpoint, $apiKey = null)
    {
        $this->apiRequest = $apiRequest;
        $this->setEndpoint($endpoint);
        $this->setApiKey($apiKey);
        $this->setLogger(new NullLogger());
    }

    /**
     * Download translations
     *
     * @param string $path save downloaded file to this path
     * @param string $package
     *
     * @return mixed
     */
    public function download($path, $package = null)
    {
        // TODO: Implement download() method.
    }

    /**
     * Fetch statistic
     *
     * @param array $packages
     *
     * @return array
     */
    public function fetchStatistic(array $packages = [])
    {
        $data = [];

        return $data;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Sets a logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Perform request
     *
     * @param string $uri
     * @param array  $curlOptions
     *
     * @throws \RuntimeException
     * @return mixed
     */
    protected function request($uri, $curlOptions = [])
    {
        $requestParams = [
                CURLOPT_URL            => $this->endpoint . $uri . '?key=' . $this->apiKey,
                CURLOPT_RETURNTRANSFER => true,
            ] + $curlOptions;
        $this->apiRequest->setOptions($requestParams);

        return $this->apiRequest->execute();
    }
}

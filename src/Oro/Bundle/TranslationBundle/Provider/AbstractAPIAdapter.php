<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Psr\Log\LoggerInterface;

abstract class AbstractAPIAdapter
{
    /** @var string */
    protected $apiKey;

    /** @var string endpoint URL */
    protected $endpoint;

    /** @var LoggerInterface */
    protected $logger;

    /** @var ApiRequestInterface */
    protected $apiRequest;

    public function __construct($endpoint, ApiRequestInterface $apiRequest)
    {
        $this->endpoint  = $endpoint;
        $this->apiRequest = $apiRequest;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Upload source files to translation service
     *
     * @param string $files file list with translations
     * @param string $mode 'update' or 'add'
     *
     * @return mixed
     */
    abstract public function upload($files, $mode = 'add');

    /**
     * Download translations
     *
     * @param string $path save downloaded file to this path
     * @param string $package
     *
     * @return mixed
     */
    abstract public function download($path, $package = null);

    /**
     * Perform request
     *
     * @param string $uri
     * @param array  $data
     * @param string $method
     * @param array  $curlOptions
     *
     * @throws \RuntimeException
     * @return mixed
     */
    protected function request($uri, $data = array(), $method = 'GET', $curlOptions = [])
    {
        $requestParams = [
                CURLOPT_URL            => $this->endpoint . $uri . '?key=' . $this->apiKey,
                CURLOPT_RETURNTRANSFER => true,
            ] + $curlOptions;

        if ($method == 'POST') {
            $requestParams[CURLOPT_POST] = true;
            $requestParams[CURLOPT_POSTFIELDS] = $data;
        }

        $this->apiRequest->reset();
        $this->apiRequest->setOptions($requestParams);

        return $this->apiRequest->execute();
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
}

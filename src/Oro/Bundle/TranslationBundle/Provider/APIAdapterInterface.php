<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Psr\Log\LoggerInterface;

interface APIAdapterInterface
{
    /**
     * Download translations
     *
     * @param string $path save downloaded file to this path
     * @param array  $projects project names, some adapters may need it
     * @param string $package package or locale (e.g. for crowdin)
     *
     * @return mixed
     */
    public function download($path, array $projects, $package = null);

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
    public function request($uri, $data = array(), $method = 'GET', $curlOptions = []);

    /**
     * Sets a logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger);

    /**
     * @param $response
     *
     * @return \stdObject
     * @throws \RuntimeException
     */
    public function parseResponse($response);
} 
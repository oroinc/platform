<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Psr\Log\LoggerAwareInterface;

interface APIAdapterInterface extends LoggerAwareInterface
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
     * @param array  $options
     *
     * @throws \RuntimeException
     * @return mixed
     */
    public function request($uri, $data = array(), $method = 'GET', $options = []);

    /**
     * @param $response
     *
     * @return \stdClass
     * @throws \RuntimeException
     */
    public function parseResponse($response);
}

<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Psr\Log\LoggerAwareInterface;

use Guzzle\Http\Message\Response;

interface APIAdapterInterface extends LoggerAwareInterface
{
    /**
     * Download translations
     *
     * @param string $path save downloaded file to this path
     * @param array  $projects project names
     * @param string $package package or locale (e.g. for crowdin)
     *
     * @return mixed
     */
    public function download($path, array $projects = [], $package = null);

    /**
     * Perform request
     *
     * @param string $uri
     * @param array  $data
     * @param string $method
     * @param array  $options
     * @param array  $headers
     *
     * @throws \RuntimeException
     *
     * @return Response
     */
    public function request($uri, $data = array(), $method = 'GET', $options = [], $headers = []);
}

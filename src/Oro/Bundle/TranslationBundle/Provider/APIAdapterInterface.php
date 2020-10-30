<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * An interface to define the translation API adapter
 */
interface APIAdapterInterface extends LoggerAwareInterface
{
    /**
     * Download translations
     *
     * @param string      $path save downloaded file to this path
     * @param array       $projects project names
     * @param string|null $package package or locale (e.g. for crowdin)
     *
     * @return mixed
     */
    public function download($path, array $projects = [], string $package = null);

    /**
     * Perform request
     *
     * @param string $uri
     * @param array  $data
     * @param string $method
     * @param array  $options
     * @param array  $headers
     *
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function request($uri, $data = [], $method = 'GET', $options = [], $headers = []): ResponseInterface;
}

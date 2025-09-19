<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * The helper class to check whether the current request is an API request or not.
 */
class ApiRequestHelper
{
    public function __construct(
        private readonly array $apiPatterns
    ) {
    }

    /**
     * Checks whether the given URL represents an API request or not.
     *
     * @param string $pathinfo The path info to be checked (raw format, i.e. not urldecoded)
     *                         {@see \Symfony\Component\HttpFoundation\Request::getPathInfo}
     *
     * @return bool
     */
    public function isApiRequest(string $pathinfo): bool
    {
        foreach ($this->apiPatterns as $apiPattern) {
            if (preg_match('{' . $apiPattern . '}', $pathinfo) === 1) {
                return true;
            }
        }

        return false;
    }
}

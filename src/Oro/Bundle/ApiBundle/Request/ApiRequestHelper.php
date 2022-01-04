<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * The helper class to check whether the current request is an API request or not.
 */
class ApiRequestHelper
{
    private string $apiPattern;

    public function __construct(string $apiPattern)
    {
        $this->apiPattern = $apiPattern;
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
        return preg_match('{' . $this->apiPattern . '}', $pathinfo) === 1;
    }
}

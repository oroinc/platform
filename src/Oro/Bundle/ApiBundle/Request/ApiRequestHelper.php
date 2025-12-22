<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * The helper class to check whether the current request is an API request or not.
 */
class ApiRequestHelper
{
    private readonly array $apiPatterns;
    private array $cache = [];

    public function __construct(
        array $apiPatterns
    ) {
        $patterns = [];
        foreach ($apiPatterns as $apiPattern) {
            $patterns[] = '{' . $apiPattern . '}';
        }
        $this->apiPatterns = $patterns;
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
        if (isset($this->cache[$pathinfo])) {
            return $this->cache[$pathinfo];
        }

        $result = false;
        foreach ($this->apiPatterns as $apiPattern) {
            if (preg_match($apiPattern, $pathinfo) === 1) {
                $result = true;
            }
        }
        $this->cache[$pathinfo] = $result;

        return $result;
    }
}

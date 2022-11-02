<?php

namespace Oro\Component\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * The utility class that provides static methods to help matching a URL path with a set of routes.
 */
class UrlMatcherUtil
{
    /**
     * Tries to match a URL path for GET HTTP method with a set of routes.
     *
     * @param string              $pathInfo   The path info to be parsed
     * @param UrlMatcherInterface $urlMatcher The URL matcher
     *
     * @return array An array of parameters
     *
     * @throws NoConfigurationException  If no routing configuration could be found
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    public static function matchForGetMethod(string $pathInfo, UrlMatcherInterface $urlMatcher): array
    {
        $context = $urlMatcher->getContext();
        $originalMethod = $context->getMethod();
        $originalPathinfo = $context->getPathInfo();
        $context->setMethod(Request::METHOD_GET);
        $context->setPathInfo($pathInfo);
        try {
            return $urlMatcher->match($pathInfo);
        } finally {
            $context->setMethod($originalMethod);
            $context->setPathInfo($originalPathinfo);
        }
    }
}

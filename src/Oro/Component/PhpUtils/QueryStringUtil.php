<?php

namespace Oro\Component\PhpUtils;

/**
 * Provides a set of reusable static methods to build URL-encoded query string.
 */
final class QueryStringUtil
{
    /**
     * Builds URL-encoded query string.
     *
     * @param array $params [key => string value, ...]
     *
     * @return string
     */
    public static function buildQueryString(array $params): string
    {
        // assert that all values are string
        foreach ($params as $name => $value) {
            if (!is_string($value)) {
                throw new \UnexpectedValueException(sprintf(
                    'Expected string value for the parameter "%s", given "%s".',
                    $name,
                    is_object($value) ? get_class($value) : gettype($value)
                ));
            }
        }

        return self::httpBuildQuery($params);
    }

    /**
     * Adds URL-encoded query string to URL.
     *
     * @param string $url
     * @param string $queryString
     *
     * @return string
     */
    public static function addQueryString(string $url, string $queryString): string
    {
        if ($queryString) {
            $url .= (false === strrpos($url, '?') ? '?' : '&') . $queryString;
        }

        return $url;
    }

    /**
     * Adds a parameter to URL-encoded query string.
     * If the query string already contains a parameter with the given name
     * its value will be overridden with the new value.
     *
     * @param string $queryString
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    public static function addParameter(string $queryString, string $name, string $value): string
    {
        parse_str($queryString, $params);

        parse_str(self::httpBuildQuery([urldecode($name) => $value]), $addingParam);
        $params = array_replace_recursive($params, $addingParam);

        return self::httpBuildQuery($params);
    }

    /**
     * Removes a parameter from URL-encoded query string.
     *
     * @param string $queryString
     * @param string $name
     *
     * @return string
     */
    public static function removeParameter(string $queryString, string $name): string
    {
        parse_str($queryString, $params);

        parse_str(self::httpBuildQuery([urldecode($name) => '']), $removingParam);
        $path = [];
        $val = reset($removingParam);
        $path[] = key($removingParam);
        while (is_array($val)) {
            $path[] = key($val);
            $val = reset($val);
        }

        return self::httpBuildQuery(self::unsetParameterByPath($params, $path));
    }

    /**
     * @param array $params
     *
     * @return string
     */
    private static function httpBuildQuery(array $params): string
    {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param array $params
     * @param array $path
     *
     * @return array
     */
    private static function unsetParameterByPath(array $params, array $path)
    {
        $key = array_shift($path);
        if (empty($path)) {
            unset($params[$key]);
        } elseif (array_key_exists($key, $params) && is_array($params[$key])) {
            $value = self::unsetParameterByPath($params[$key], $path);
            if (empty($value)) {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}

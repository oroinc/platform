<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Provides a functionality to parse a media type header value.
 */
final class MediaTypeHeaderUtil
{
    /**
     * Parses the given media type header value.
     *
     * @param string $headerValue
     *
     * @return array [media type, media type parameters]
     */
    public static function parseMediaType(string $headerValue): array
    {
        $mediaType = $headerValue;
        $parameters = [];
        $mediaTypeDelimiterPos = strpos($headerValue, ';');
        if (false !== $mediaTypeDelimiterPos) {
            $mediaType = substr($headerValue, 0, $mediaTypeDelimiterPos);
            $parameters = self::parseParameters(substr($headerValue, $mediaTypeDelimiterPos + 1));
        }

        return [$mediaType, $parameters];
    }

    /**
     * @param string $headerValue
     *
     * @return array [key => value, ...]
     */
    private static function parseParameters(string $headerValue): array
    {
        $parameters = [];
        $parts = HeaderUtils::split($headerValue, ';=');
        foreach ($parts as [$key, $val]) {
            if (isset($parameters[$key])) {
                if (!\is_array($parameters[$key])) {
                    $parameters[$key] = [$parameters[$key]];
                }
                $parameters[$key][] = $val;
            } else {
                $parameters[$key] = $val;
            }
        }

        return $parameters;
    }
}

<?php

namespace Oro\Bundle\SyncBundle\Authentication\Origin;

/**
 * Contains methods to extract origin from different sources
 */
class OriginExtractor
{
    /**
     * @param null|string $url
     * @return null|string
     */
    public function fromUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }
        $url = ltrim(trim($url), '/');

        if (strpos($url, '://') === false) {
            // just "parse_url" will work properly
            $url = "http://{$url}";
        }

        $parts = parse_url($url);

        if (!is_array($parts)) {
            return null;
        }

        if (array_key_exists('path', $parts) && $parts['path'] == $url && strpos($parts['path'], '/') === false) {
            //just domain.com was passed
            return $parts['path'];
        }

        if (!array_key_exists('host', $parts) || empty($parts['host'])) {
            return null;
        }

        return $parts['host'];
    }
}

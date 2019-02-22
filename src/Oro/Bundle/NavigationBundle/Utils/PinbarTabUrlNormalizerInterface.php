<?php

namespace Oro\Bundle\NavigationBundle\Utils;

/**
 * Interface for clases which normalize PinbarTab URL.
 */
interface PinbarTabUrlNormalizerInterface
{
    /**
     * @param string $url
     *
     * @return string
     */
    public function getNormalizedUrl(string $url): string;
}

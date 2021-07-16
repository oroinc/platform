<?php

namespace Oro\Bundle\NavigationBundle\Utils;

/**
 * Interface for clases which normalize PinbarTab URL.
 */
interface PinbarTabUrlNormalizerInterface
{
    public function getNormalizedUrl(string $url): string;
}

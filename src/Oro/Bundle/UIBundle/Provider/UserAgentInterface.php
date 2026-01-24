<?php

namespace Oro\Bundle\UIBundle\Provider;

/**
 * Defines the contract for user agent detection and analysis.
 *
 * Provides methods to retrieve the raw user agent string and determine device type
 * (mobile vs. desktop) from the HTTP request headers.
 */
interface UserAgentInterface
{
    /**
     * Returns the user agent string.
     *
     * @return string
     */
    public function getUserAgent();

    /**
     * Indicates if the request is from a mobile device.
     *
     * @return bool
     */
    public function isMobile();

    /**
     * Indicates if the request is from a desktop device.
     *
     * @return bool
     */
    public function isDesktop();
}

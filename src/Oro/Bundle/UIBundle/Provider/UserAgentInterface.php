<?php

namespace Oro\Bundle\UIBundle\Provider;

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

<?php

namespace Oro\Bundle\UIBundle\Provider;

interface UserAgentInterface extends \ArrayAccess
{
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

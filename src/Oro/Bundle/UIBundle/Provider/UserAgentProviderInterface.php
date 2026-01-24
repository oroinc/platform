<?php

namespace Oro\Bundle\UIBundle\Provider;

/**
 * Defines the contract for providing user agent instances.
 *
 * Implementations should return a {@see UserAgent} instance that can be used to analyze
 * the current request's user agent string and determine device characteristics.
 */
interface UserAgentProviderInterface
{
    /**
     * Returns the user agent instance.
     *
     * @return UserAgent
     */
    public function getUserAgent();
}

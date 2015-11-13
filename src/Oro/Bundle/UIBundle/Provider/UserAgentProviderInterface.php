<?php

namespace Oro\Bundle\UIBundle\Provider;

interface UserAgentProviderInterface
{
    /**
     * Returns the user agent instance.
     *
     * @return UserAgent
     */
    public function getUserAgent();
}

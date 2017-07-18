<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;

class FakeUserAgentProvider implements UserAgentProviderInterface
{
    public $isDesktop = true;

    /**
     * {@inheritdoc}
     */
    public function getUserAgent()
    {
        return new FakeUserAgent($this->isDesktop);
    }
}

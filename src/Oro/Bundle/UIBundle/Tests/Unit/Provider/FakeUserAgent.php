<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\UserAgentInterface;

class FakeUserAgent implements UserAgentInterface
{
    public $isDesktop = true;

    /**
     * @param bool $isDesktop
     */
    public function __construct($isDesktop = true)
    {
        $this->isDesktop = $isDesktop;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAgent()
    {
        return 'fake';
    }

    /**
     * {@inheritdoc}
     */
    public function isMobile()
    {
        return !$this->isDesktop();
    }

    /**
     * {@inheritdoc}
     */
    public function isDesktop()
    {
        return $this->isDesktop;
    }
}

<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\Provider\UserAgentProvider;

class MobileExtension extends \Twig_Extension
{
    /** @var UserAgentProvider */
    protected $userAgentProvider;

    /**
     * @param UserAgentProvider $userAgentProvider
     */
    public function __construct(UserAgentProvider $userAgentProvider)
    {
        $this->userAgentProvider = $userAgentProvider;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('isMobileVersion', array($this, 'isMobile')),
            new \Twig_SimpleFunction('isDesktopVersion', array($this, 'isDesktop'))
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'mobile_extension';
    }

    /**
     * Check by user-agent if request was from mobile device
     *
     * @return bool
     */
    public function isMobile()
    {
        return $this->userAgentProvider->getUserAgent()->isMobile();
    }


    /**
     * Check by user-agent if request was not from mobile device
     *
     * @return bool
     */
    public function isDesktop()
    {
        return $this->userAgentProvider->getUserAgent()->isDesktop();
    }
}

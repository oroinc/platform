<?php

namespace Oro\Bundle\UIBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UIBundle\Provider\UserAgent;

/**
 * IMPORTANT: it is not recommended to use this extension in Layout related TWIG templates,
 *            there you should use context depended layout updates.
 *            See details in the documentation for LayoutBundle.
 */
class MobileExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /** @var UserAgent[] */
    protected $cache = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
     * IMPORTANT: it is not recommended to use this function in Layout related TWIG templates,
     *            there you should use context depended layout updates.
     *            See details in the documentation for LayoutBundle.
     *
     * @return bool
     */
    public function isMobile()
    {
        $request   = $this->container->get('request');
        $userAgent = $request->headers->get('User-Agent');

        if (isset($this->cache[$userAgent])) {
            $agent = $this->cache[$userAgent];
        } else {
            $agent = new UserAgent($userAgent);

            $this->cache[$userAgent] = $agent;
        }

        return $agent->isMobile();
    }


    /**
     * Check by user-agent if request was not from mobile device
     *
     * IMPORTANT: it is not recommended to use this function in Layout related TWIG templates,
     *            there you should use context depended layout updates.
     *            See details in the documentation for LayoutBundle.
     *
     * @return bool
     */
    public function isDesktop()
    {
        return !$this->isMobile();
    }
}

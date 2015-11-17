<?php

namespace Oro\Bundle\UIBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;

class UserAgentProvider implements UserAgentProviderInterface
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
     * @return UserAgent
     */
    public function getUserAgent()
    {
        $request   = $this->container->get('request');
        $userAgent = $request->headers->get('User-Agent');

        if (isset($this->cache[$userAgent])) {
            $agent = $this->cache[$userAgent];
        } else {
            $agent = new UserAgent($userAgent);

            $this->cache[$userAgent] = $agent;
        }

        return $agent;
    }
}

<?php

namespace Oro\Bundle\UIBundle\Provider;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides UserAgent based on User-Agent header value
 */
class UserAgentProvider implements UserAgentProviderInterface
{
    const UNKNOWN_USER_AGENT = 'unknown_user_agent';

    /** @var RequestStack */
    protected $requestStack;

    /** @var UserAgent[] */
    protected $cache = [];

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAgent()
    {
        $userAgentName = self::UNKNOWN_USER_AGENT;

        $request = $this->requestStack->getMainRequest();
        if ($request) {
            /** @var string $userAgentName */
            $userAgentHeader = $request->headers->get('User-Agent');
            if ($userAgentHeader) {
                $userAgentName = $userAgentHeader;
            }
        }

        if (!array_key_exists($userAgentName, $this->cache)) {
            $this->cache[$userAgentName] = new UserAgent($userAgentName);
        }

        return $this->cache[$userAgentName];
    }
}

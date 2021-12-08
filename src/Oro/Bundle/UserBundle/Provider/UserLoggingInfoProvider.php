<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides basic user info for logging purposes.
 */
class UserLoggingInfoProvider implements UserLoggingInfoProviderInterface
{
    /** @var RequestStack */
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param User|string $user
     * @return array
     */
    public function getUserLoggingInfo($user): array
    {
        $info = [];
        if ($user instanceof User) {
            $info['user'] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'fullname' => $user->getFullName(),
                'enabled' => $user->isEnabled(),
                'lastlogin' => $user->getLastLogin(),
                'createdat' => $user->getCreatedAt(),
            ];
        } elseif (\is_string($user)) {
            $info['username'] = $user;
        }

        $ip = $this->getIp();
        if ($ip) {
            $info['ipaddress'] = $ip;
        }

        return $info;
    }

    protected function getIp(): ?string
    {
        if (!$this->requestStack->getCurrentRequest()) {
            return null;
        }

        return $this->requestStack->getCurrentRequest()->getClientIp();
    }
}

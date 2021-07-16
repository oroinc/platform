<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This provider provides basic user info for logging purposes
 */
class UserLoggingInfoProvider
{
    /** @var RequestStack */
    private $requestStack;

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
        if (!$user instanceof User) {
            $info['username'] = $user;
        } else {
            $info['user'] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'fullname' => $user->getFullName(),
                'enabled' => $user->isEnabled(),
                'lastlogin' => $user->getLastLogin(),
                'createdat' => $user->getCreatedAt(),
            ];
        }
        $ip = $this->getIp();
        if ($ip) {
            $info['ipaddress'] = $ip;
        }
        return $info;
    }

    /**
     * @return string|null
     */
    private function getIp()
    {
        if (!$this->requestStack->getCurrentRequest()) {
            return null;
        }
        return $this->requestStack->getCurrentRequest()->getClientIp();
    }
}

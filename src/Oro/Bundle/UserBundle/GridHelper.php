<?php

namespace Oro\Bundle\UserBundle;

use Symfony\Component\Security\Core\SecurityContext;

class GridHelper
{
    /**
     * @var \Symfony\Component\Security\Core\SecurityContext
     */
    protected $securityContext;

    /**
     * @param SecurityContext $securityContext
     */
    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * Get current user id.
     *
     * @return int
     */
    public function getUserId()
    {
        if (null === $token = $this->securityContext->getToken()) {
            return -1;
        }

        if (!is_object($user = $token->getUser())) {
            return -1;
        }

        return $user->getId();
    }
}

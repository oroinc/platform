<?php

namespace Oro\Bundle\UserBundle\Placeholder;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class PlaceholderFilter
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * Checks if password management is available
     *
     * @param object $entity
     * @return bool
     */
    public function isPasswordManageEnabled($entity)
    {
        if ($entity instanceof User &&
            $entity->getAuthStatus() &&
            $entity->getAuthStatus()->getId() === UserManager::STATUS_EXPIRED
        ) {
            return false;
        }

        return $entity instanceof User && $entity->isEnabled();
    }

    /**
     * Checks if password can be reset
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isPasswordResetEnabled($entity)
    {
        return $entity instanceof User && $entity->isEnabled();
    }

    /**
     * @return bool
     */
    public function isUserApplicable()
    {
        return $this->securityFacade->getLoggedUser() instanceof User;
    }
}

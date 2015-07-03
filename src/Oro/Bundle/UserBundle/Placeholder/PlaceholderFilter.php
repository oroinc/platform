<?php

namespace Oro\Bundle\UserBundle\Placeholder;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

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
     * Checks if the object is an instance of a given class.
     *
     * @param object $entity
     * @return bool
     */
    public function isPasswordManageEnabled($entity)
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

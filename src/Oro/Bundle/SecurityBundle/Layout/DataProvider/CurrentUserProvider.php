<?php

namespace Oro\Bundle\SecurityBundle\Layout\DataProvider;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class CurrentUserProvider
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @return object|null
     */
    public function getCurrentUser()
    {
        return $this->securityFacade->getLoggedUser();
    }
}

<?php

namespace Oro\Bundle\SecurityBundle\Layout\Extension;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class SecurityFacadeContextConfigurator implements ContextConfiguratorInterface
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
     * {@inheritDoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setRequired(['logged_user'])
            ->setAllowedTypes([
                'logged_user' => ['Symfony\Component\Security\Core\User\UserInterface', 'string', 'null']
            ]);

        $context->set('logged_user', $this->securityFacade->getLoggedUser());
    }
}

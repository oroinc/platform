<?php

namespace Oro\Bundle\SecurityBundle\Layout\Extension;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class IsLoggedInContextConfigurator implements
    ContextConfiguratorInterface
{
    const OPTION_NAME = 'is_logged_in';

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
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setRequired([self::OPTION_NAME])
            ->setAllowedTypes([
                self::OPTION_NAME => ['bool']
            ]);

        $context->set(self::OPTION_NAME, $this->securityFacade->hasLoggedUser());
    }
}

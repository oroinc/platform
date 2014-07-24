<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class LoggedUserVariablesProvider implements VariablesProviderInterface
{
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
    public function getTemplateVariables(array $context = [])
    {
        // @todo: not implemented yet
        return [];
    }
}

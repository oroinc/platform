<?php

namespace Oro\Bundle\SecurityBundle\Layout\Extension;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Configures the layout context with the user's login status.
 *
 * This configurator adds the `is_logged_in` option to the layout context,
 * indicating whether the current user is authenticated. This information can be
 * used by layout blocks and templates to conditionally render content based on
 * the user's authentication status.
 */
class IsLoggedInContextConfigurator implements ContextConfiguratorInterface
{
    const OPTION_NAME = 'is_logged_in';

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    #[\Override]
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setRequired([self::OPTION_NAME])
            ->setAllowedTypes(self::OPTION_NAME, ['bool']);

        $context->set(self::OPTION_NAME, $this->tokenAccessor->hasUser());
    }
}

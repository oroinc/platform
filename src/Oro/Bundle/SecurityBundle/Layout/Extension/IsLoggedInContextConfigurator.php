<?php

namespace Oro\Bundle\SecurityBundle\Layout\Extension;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class IsLoggedInContextConfigurator implements ContextConfiguratorInterface
{
    const OPTION_NAME = 'is_logged_in';

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setRequired([self::OPTION_NAME])
            ->setAllowedTypes(self::OPTION_NAME, ['bool']);

        $context->set(self::OPTION_NAME, $this->tokenAccessor->hasUser());
    }
}

<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;

class UserAgentContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * @var UserAgentProvider
     */
    protected $userAgentProvider;

    /**
     * @param UserAgentProvider $userAgentProvider
     */
    public function __construct(UserAgentProvider $userAgentProvider)
    {
        $this->userAgentProvider = $userAgentProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setRequired(['user_agent'])
            ->setAllowedTypes(['user_agent' => ['Oro\Bundle\UIBundle\Provider\UserAgentInterface']]);

        $context->set('user_agent', $this->userAgentProvider->getUserAgent());
    }
}

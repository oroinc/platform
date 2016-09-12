<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;

class IsMobileContextConfigurator implements ContextConfiguratorInterface
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
            ->setRequired(['is_mobile'])
            ->setAllowedTypes(['is_mobile' => ['boolean']]);

        $context->set('is_mobile', $this->userAgentProvider->getUserAgent()->isMobile());
    }
}

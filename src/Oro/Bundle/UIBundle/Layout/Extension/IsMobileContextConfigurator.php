<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class IsMobileContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * @var UserAgentProvider
     */
    protected $userAgentProvider;

    public function __construct(UserAgentProvider $userAgentProvider)
    {
        $this->userAgentProvider = $userAgentProvider;
    }

    #[\Override]
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setRequired(['is_mobile'])
            ->setAllowedTypes('is_mobile', ['boolean']);

        $context->set('is_mobile', $this->userAgentProvider->getUserAgent()->isMobile());
    }
}

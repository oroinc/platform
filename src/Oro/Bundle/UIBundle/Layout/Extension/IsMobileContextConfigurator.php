<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Configures layout context with mobile device detection.
 *
 * Analyzes the user agent to determine if the request is from a mobile device and
 * sets the 'is_mobile' context variable accordingly. This enables layouts to adapt
 * their rendering based on the device type.
 */
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

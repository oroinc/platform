<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is dispatched default owner set for existing integration instance.
 * It's aimed to handle situations when integration was created before 1.3(when integration did not have default owner)
 *
 * @package Oro\Bundle\IntegrationBundle\Event
 */
class DefaultOwnerSetEvent extends Event
{
    const NAME = 'oro_integration.default_owner.set';

    /** @var Integration */
    protected $integration;

    /**
     * @param Integration $integration
     */
    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * @return Integration
     */
    public function getChannel()
    {
        return $this->integration;
    }

    /**
     * @return User
     */
    public function getDefaultUserOwner()
    {
        return $this->integration->getDefaultUserOwner();
    }
}

<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

class IntegrationUpdateEvent extends Event
{
    const NAME = 'oro_integration.integration_update';

    /**
     * @var Integration
     */
    protected $integration;

    /**
     * @var Integration
     */
    protected $oldState;

    /**
     * @param Integration $integration
     * @param Integration $oldState
     */
    public function __construct(Integration $integration, Integration $oldState)
    {
        $this->integration = $integration;
        $this->oldState    = $oldState;
    }

    /**
     * @return Integration
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * @return Integration
     */
    public function getOldState()
    {
        return $this->oldState;
    }
}

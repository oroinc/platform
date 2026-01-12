<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when an integration channel is updated.
 *
 * This event carries both the updated integration state and its previous state,
 * allowing listeners to detect what has changed and react accordingly.
 * Listeners can use this event to perform additional processing or validation
 * when an integration channel configuration is modified.
 */
class IntegrationUpdateEvent extends Event
{
    public const NAME = 'oro_integration.integration_update';

    /**
     * @var Integration
     */
    protected $integration;

    /**
     * @var Integration
     */
    protected $oldState;

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

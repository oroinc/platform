<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event is dispatched before loading of data fixtures for functional tests
 * and it is intended to collect event listeners that should be disabled during
 * loading of these data fixtures.
 */
class DisableListenersForDataFixturesEvent extends Event
{
    public const NAME = 'oro_test.collect_listeners_disabled_for_data_fixtures';

    /** @var string[] */
    private $listeners = [];

    /**
     * @return string[]
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * @param string $listenerServiceId
     */
    public function addListener($listenerServiceId)
    {
        $this->listeners[] = $listenerServiceId;
    }
}

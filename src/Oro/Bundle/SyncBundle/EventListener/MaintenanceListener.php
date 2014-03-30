<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;

class MaintenanceListener
{
    /**
     * @var TopicPublisher
     */
    protected $publisher;

    /**
     * @param TopicPublisher $publisher
     */
    public function __construct(TopicPublisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function onModeOn()
    {
        $this->publisher->send('oro/maintenance', array('isOn' => true));
    }

    public function onModeOff()
    {
        $this->publisher->send('oro/maintenance', array('isOn' => false));
    }
}

<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;

class MaintenanceListener
{
    /**
     * @var TopicPublisher
     */
    protected $publisher;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param TopicPublisher $publisher
     * @param SecurityFacade $securityFacade
     */
    public function __construct(TopicPublisher $publisher, SecurityFacade $securityFacade)
    {
        $this->publisher = $publisher;
        $this->securityFacade = $securityFacade;
    }

    public function onModeOn()
    {
        $userId = $this->securityFacade->getLoggedUserId();

        $this->publisher->send('oro/maintenance', array('isOn' => true, 'userId' => $userId));
    }

    public function onModeOff()
    {
        $userId = $this->securityFacade->getLoggedUserId();

        $this->publisher->send('oro/maintenance', array('isOn' => false, 'userId' => $userId));
    }
}

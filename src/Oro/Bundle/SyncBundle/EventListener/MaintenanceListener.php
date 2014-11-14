<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;

use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param TopicPublisher $publisher
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        TopicPublisher $publisher,
        SecurityFacade $securityFacade,
        LoggerInterface $logger
    ) {
        $this->publisher      = $publisher;
        $this->securityFacade = $securityFacade;
        $this->logger         = $logger;
    }

    public function onModeOn()
    {
        $userId = $this->securityFacade->getLoggedUserId();

        try {
            $this->publisher->send('oro/maintenance', array('isOn' => true, 'userId' => $userId));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function onModeOff()
    {
        $userId = $this->securityFacade->getLoggedUserId();

        try {
            $this->publisher->send('oro/maintenance', array('isOn' => false, 'userId' => $userId));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}

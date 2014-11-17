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
     * @param LoggerInterface $logger
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
        $this->onMode(true);
    }

    public function onModeOff()
    {
        $this->onMode(false);
    }

    /**
     * @param bool $isOn
     */
    protected function onMode($isOn)
    {
        $userId = $this->securityFacade->getLoggedUserId();

        try {
            $this->publisher->send('oro/maintenance', array('isOn' => (bool)$isOn, 'userId' => $userId));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}

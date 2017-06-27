<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Psr\Log\LoggerInterface;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;

class MaintenanceListener
{
    /** @var TopicPublisher */
    protected $publisher;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param TopicPublisher         $publisher
     * @param TokenAccessorInterface $tokenAccessor
     * @param LoggerInterface        $logger
     */
    public function __construct(
        TopicPublisher $publisher,
        TokenAccessorInterface $tokenAccessor,
        LoggerInterface $logger
    ) {
        $this->publisher = $publisher;
        $this->tokenAccessor = $tokenAccessor;
        $this->logger = $logger;
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
        $userId = $this->tokenAccessor->getUserId();

        try {
            $this->publisher->send('oro/maintenance', array('isOn' => (bool)$isOn, 'userId' => $userId));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}

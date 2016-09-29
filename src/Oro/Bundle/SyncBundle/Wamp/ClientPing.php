<?php

namespace Oro\Bundle\SyncBundle\Wamp;

use JDare\ClankBundle\Periodic\PeriodicInterface;
use Psr\Log\LoggerInterface;

class ClientPing implements PeriodicInterface
{
    /** @var TopicPublisher */
    protected $publisher;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param TopicPublisher  $publisher
     * @param LoggerInterface $logger
     */
    public function __construct(TopicPublisher $publisher, LoggerInterface $logger)
    {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function tick()
    {
        try {
            $this->publisher->send('oro/ping', 'Connection keep alive');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}

<?php

namespace Oro\Bundle\NotificationBundle\Manager;

use Oro\Bundle\NotificationBundle\Model\NotificationInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

use Psr\Log\LoggerInterface;

abstract class AbstractNotificationManager
{
    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @var string
     */
    protected $topic;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    protected function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Sends the notifications
     *
     * @param mixed                        $object
     * @param NotificationInterface[]      $notifications
     * @param LoggerInterface              $logger Override for default logger. If this parameter is specified
     *                                             this logger will be used instead of a logger specified
     *                                             in the constructor
     * @param array                        $params Additional params for template renderer
     */
    abstract public function process($object, $notifications, LoggerInterface $logger = null, $params = []);

    /**
     * @param array $messageParams
     */
    protected function sendMessage($messageParams = [])
    {
        $this->producer->send($this->topic, $messageParams);
    }
}
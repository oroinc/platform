<?php

namespace Oro\Bundle\CronBundle\EventListener;

use JMS\JobQueueBundle\Event\NewOutputEvent;

use Psr\Log\LoggerInterface;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JobSubscriber implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'jms_job_queue.new_job_output' => ['onNewOutput', -255],
        ];
    }

    /**
     * @param NewOutputEvent $event
     *
     * @todo Remove this listener once we get rid of dependency on JMSJobQueueBundle
     */
    public function onNewOutput(NewOutputEvent $event)
    {
        $type = $event->getType() === NewOutputEvent::TYPE_STDOUT ? 'debug' : 'error';
        call_user_func([$this->logger, $type], $event->getNewOutput(), ['job' => $event->getJob()]);
    }
}

<?php

namespace Oro\Bundle\NotificationBundle\Provider\Mailer;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\NotificationBundle\Entity\SpoolItem;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;

class DbSpool extends \Swift_ConfigurableSpool
{
    const STATUS_FAILED     = 0;
    const STATUS_READY      = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_COMPLETE   = 3;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EntityPool
     */
    protected $entityPool;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var string
     */
    protected $logType;

    /**
     * @param EntityManager $em
     * @param EntityPool $entityPool
     * @param string $entityClass
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EntityManager $em,
        EntityPool $entityPool,
        $entityClass,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->entityPool = $entityPool;
        $this->entityClass = $entityClass;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Starts this Spool mechanism.
     */
    public function start()
    {
    }

    /**
     * Stops this Spool mechanism.
     */
    public function stop()
    {
    }

    /**
     * Tests if this Spool mechanism has started.
     *
     * @return boolean
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function queueMessage(\Swift_Mime_Message $message)
    {
        /** @var SpoolItem $mailObject */
        $mailObject = new $this->entityClass;
        $mailObject->setMessage($message);
        $mailObject->setStatus(self::STATUS_READY);
        $mailObject->setLogType($this->logType);

        $this->entityPool->addPersistEntity($mailObject);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flushQueue(\Swift_Transport $transport, &$failedRecipients = null)
    {
        if (!$transport->isStarted()) {
            $transport->start();
        }

        $repo = $this->em->getRepository($this->entityClass);
        $limit = $this->getMessageLimit();
        $limit = $limit > 0 ? $limit : null;
        $emails = $repo->findBy(array("status" => self::STATUS_READY), null, $limit);
        if (!count($emails)) {
            return 0;
        }

        $failedRecipients = (array)$failedRecipients;
        $count = 0;
        $time = time();
        /** @var SpoolItem $email */
        foreach ($emails as $email) {
            $email->setStatus(self::STATUS_PROCESSING);
            $this->em->persist($email);
            $this->em->flush($email);
            $sentCount = $transport->send($email->getMessage(), $failedRecipients);
            $count += $sentCount;
            $this->eventDispatcher->dispatch(
                NotificationSentEvent::NAME,
                new NotificationSentEvent($email, $sentCount)
            );
            $this->em->remove($email);
            $this->em->flush($email);

            if ($this->getTimeLimit() && (time() - $time) >= $this->getTimeLimit()) {
                break;
            }
        }

        return $count;
    }

    /**
     * @param string $logType
     */
    public function setLogType($logType)
    {
        $this->logType = $logType;
    }
}

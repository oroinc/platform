<?php

namespace Oro\Bundle\NotificationBundle\Provider\Mailer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Entity\SpoolItem;
use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Stores notification emails in the database.
 */
class DbSpool extends \Swift_ConfigurableSpool
{
    const STATUS_FAILED     = 0;
    const STATUS_READY      = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_COMPLETE   = 3;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EntityPool */
    protected $entityPool;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var string */
    protected $logType;

    /**
     * @param ManagerRegistry          $doctrine
     * @param EntityPool               $entityPool
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EntityPool $entityPool,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->doctrine = $doctrine;
        $this->entityPool = $entityPool;
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
        $mailObject = new SpoolItem();
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

        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass(SpoolItem::class);

        $limit = $this->getMessageLimit();
        $limit = $limit > 0 ? $limit : null;
        /** @var SpoolItem[] $emails */
        $emails = $em->getRepository(SpoolItem::class)
            ->findBy(['status' => self::STATUS_READY], null, $limit);
        if (!count($emails)) {
            return 0;
        }

        $failedRecipients = (array)$failedRecipients;
        $count = 0;
        $time = time();
        foreach ($emails as $email) {
            $email->setStatus(self::STATUS_PROCESSING);
            $em->persist($email);
            $em->flush($email);
            $sentCount = $transport->send($email->getMessage(), $failedRecipients);
            $count += $sentCount;
            $this->eventDispatcher->dispatch(
                NotificationSentEvent::NAME,
                new NotificationSentEvent($email, $sentCount)
            );
            $em->remove($email);
            $em->flush($email);

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

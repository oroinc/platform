<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;

class EmailThreadManager
{
    /**
     * @var EmailThreadProvider
     */
    protected $emailThreadProvider;

    /**
     * Emails for thread updates after flush
     *
     * @var Email[]
     */
    protected $queueThreadUpdate;

    /**
     * Emails for head updates after flush
     *
     * @var Email[]
     */
    protected $queueHeadUpdate;

    public function __construct(EmailThreadProvider $emailThreadProvider)
    {
        $this->emailThreadProvider = $emailThreadProvider;
        $this->resetQueueHeadUpdate();
        $this->resetQueueThreadUpdate();
    }

    /**
     * Handles onFlush event
     *
     * @param OnFlushEventArgs $event
     */
    public function handleOnFlush(OnFlushEventArgs $event)
    {
        $entityManager = $event->getEntityManager();
        $uow = $entityManager->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Email) {
                $this->addEmailToQueueThreadUpdate($entity);
            }
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Email) {
                $this->addEmailToQueueHeadUpdate($entity);
            }
        }
    }

    /**
     * Handles postFlush event
     *
     * @param PostFlushEventArgs $event
     */
    public function handlePostFlush(PostFlushEventArgs $event)
    {
        $entityManager = $event->getEntityManager();
        if ($this->getQueueThreadUpdate()) {
            $this->processThreadCreate($entityManager);
            $this->resetQueueThreadUpdate();
            $entityManager->flush();
        }
        if ($this->getQueueHeadUpdate()) {
            $this->processEmailsHeadSet($entityManager);
            $this->resetQueueHeadUpdate();
            $entityManager->flush();
        }
    }

    /**
     * Create thread if need for queued emails
     *
     * @param EntityManager $entityManager
     */
    protected function processThreadCreate(EntityManager $entityManager)
    {
        foreach ($this->getQueueThreadUpdate() as $entity) {
            $thread = $this->emailThreadProvider->getEmailThread($entityManager, $entity);
            if ($thread) {
                $entityManager->persist($thread);
                $entity->setThread($thread);
            }
            $this->updateRefs($entityManager, $entity);
        }
    }

    /**
     * Set heads of queued emails
     *
     * @param EntityManager $entityManager
     */
    protected function processEmailsHeadSet(EntityManager $entityManager)
    {
        foreach ($this->getQueueHeadUpdate() as $entity) {
            if ($entity->getThread() && $entity->getId()) {
                $threadEmails = $this->emailThreadProvider->getThreadEmails($entityManager, $entity);
                if (count($threadEmails) > 0) {
                    /** @var Email $email */
                    foreach ($threadEmails as $email) {
                        $email->setHead(false);
                        $entityManager->persist($email);
                    }
                    $email = $threadEmails[0];
                    $email->setHead(true);
                    $entityManager->persist($email);
                }
            }
        }
    }

    /**
     * Updates email references' threadId
     *
     * @param EntityManager $entityManager
     * @param Email $entity
     */
    protected function updateRefs(EntityManager $entityManager, Email $entity)
    {
        if ($entity->getThread()) {
            /** @var Email $email */
            foreach ($this->emailThreadProvider->getEmailReferences($entityManager, $entity) as $email) {
                if (!$email->getThread()) {
                    $email->setThread($entity->getThread());
                    $entityManager->persist($email);
                }
            }
        }
    }

    /**
     * Reset head update queue
     */
    public function resetQueueHeadUpdate()
    {
        $this->queueHeadUpdate = [];
    }

    /**
     * Get head update queue
     *
     * @return Email[]
     */
    public function getQueueHeadUpdate()
    {
        return $this->queueHeadUpdate;
    }

    /**
     * Add email to head update queue
     *
     * @param Email $email
     */
    public function addEmailToQueueHeadUpdate(Email $email)
    {
        $this->queueHeadUpdate[] = $email;
    }

    /**
     * Reset thread update queue
     */
    public function resetQueueThreadUpdate()
    {
        $this->queueThreadUpdate = [];
    }

    /**
     * Get tread update queue
     *
     * @return Email[]
     */
    public function getQueueThreadUpdate()
    {
        return $this->queueThreadUpdate;
    }

    /**
     * Add email to thread update queue
     *
     * @param Email $email
     */
    public function addEmailToQueueThreadUpdate(Email $email)
    {
        $this->queueThreadUpdate[] = $email;
    }
}

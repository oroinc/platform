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
     * Emails for head updates after flush
     *
     * @var Email[]
     */
    protected $queue;

    public function __construct(EmailThreadProvider $emailThreadProvider)
    {
        $this->emailThreadProvider = $emailThreadProvider;
        $this->resetQueue();
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
        $newEntities = $uow->getScheduledEntityInsertions();

        $this->handleEmailInsertions($entityManager, $newEntities);
    }

    /**
     * Handles postFlush event
     *
     * @param PostFlushEventArgs $event
     */
    public function handlePostFlush(PostFlushEventArgs $event)
    {
        $entityManager = $event->getEntityManager();
        $this->handleEmailUpdates($entityManager);
    }

    /**
     * Handles email insertions
     *
     * @param EntityManager $entityManager
     * @param array $newEntities
     */
    protected function handleEmailInsertions(EntityManager $entityManager, array $newEntities)
    {
        foreach ($newEntities as $entity) {
            if ($entity instanceof Email) {
                $entity->setThreadId($this->emailThreadProvider->getEmailThreadId($entityManager, $entity));
                $this->updateRefs($entityManager, $entity);
                $this->addEntityToUpdateQueue($entity);
            }
        }
    }

    /**
     * Handles email updates
     *
     * @param EntityManager $entityManager
     */
    protected function handleEmailUpdates(EntityManager $entityManager)
    {
        foreach ($this->queue as $entity) {
            if ($entity instanceof Email) {
                $this->updateThreadHead($entityManager, $entity);
            }
        }
        $this->resetQueue();
    }

    /**
     * Updates email references' threadId
     *
     * @param EntityManager $entityManager
     * @param Email $entity
     */
    protected function updateRefs(EntityManager $entityManager, Email $entity)
    {
        if ($entity->getThreadId()) {
            foreach ($this->emailThreadProvider->getEmailReferences($entityManager, $entity) as $email) {
                $email->setThreadId($entity->getThreadId());
                $entityManager->persist($email);
                $this->addEntityToUpdateQueue($email);
            }
        }
    }

    /**
     * @param EntityManager $entityManager
     * @param Email $entity
     */
    public function updateThreadHead(EntityManager $entityManager, Email $entity)
    {
        if ($entity->getThreadId()) {
            $threadEmails = $this->emailThreadProvider->getThreadEmails($entityManager, $entity);
            $this->resetHead($entityManager, $threadEmails);
            if (!$this->setHeadFirstNotSeenEmail($entityManager, $threadEmails)) {
                $this->setHeadFirstEmail($entityManager, $threadEmails);
            }
        }
    }

    /**
     * Set head first not seen email
     *
     * @param EntityManager $entityManager
     * @param Email[] $threadEmails
     *
     * @return bool
     */
    protected function setHeadFirstNotSeenEmail(EntityManager $entityManager, $threadEmails)
    {
        /** @var Email $email */
        foreach ($threadEmails as $email) {
            if (!$email->isSeen()) {
                $email->setHead(true);
                $entityManager->persist($email);
                return true;
            }
        }
        return false;
    }

    /**
     * Set head for first  email
     *
     * @param EntityManager $entityManager
     * @param Email[] $threadEmails
     */
    protected function setHeadFirstEmail(EntityManager $entityManager, $threadEmails)
    {
        $email = end($threadEmails);
        $email->setHead(true);
        $entityManager->persist($email);
    }

    /**
     * Reset head for thread
     *
     * @param EntityManager $entityManager
     * @param Email[] $threadEmails
     */
    protected function resetHead(EntityManager $entityManager, $threadEmails)
    {
        /** @var Email $email */
        foreach ($threadEmails as $email) {
            $email->setHead(false);
            $entityManager->persist($email);
        }
    }

    /**
     * @return array
     */
    protected function resetQueue()
    {
        $this->queue = [];
    }

    /**
     * @param $email
     */
    protected function addEntityToUpdateQueue($email)
    {
        $this->queue[] = $email;
    }
}

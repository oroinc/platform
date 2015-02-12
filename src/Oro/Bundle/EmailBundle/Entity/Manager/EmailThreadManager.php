<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;

class EmailThreadManager
{
    /**
     * @var EmailThreadProvider
     */
    protected $emailThreadProvider;

    public function __construct(EmailThreadProvider $emailThreadProvider)
    {
        $this->emailThreadProvider = $emailThreadProvider;
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
                $this->updateThreadHead($entityManager, $entity);
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
        if ($entity->getThreadId()) {
            foreach ($this->emailThreadProvider->getEmailReferences($entityManager, $entity) as $email) {
                $email->setThreadId($entity->getThreadId());
                $entityManager->persist($email);
            }
        }
    }

    /**
     * @param EntityManager $entityManager
     * @param Email $entity
     */
    protected function updateThreadHead(EntityManager $entityManager, Email $entity)
    {
        if ($entity->getThreadId()) {
            $threadEmails = $this->emailThreadProvider->getThreadEmails($entityManager, $entity);
            /** @var Email $email */
            $this->resetHead($entityManager, $threadEmails);
            if (!$entity->isSeen()) {
                $entity->setHead(true);
                $entityManager->persist($entity);
                return;
            }
            if (!$this->setHeadFirstNotSeenEmail($entityManager, $threadEmails)) {
                $this->setHeadFirstEmail($entityManager);
            }
        }
    }

    /**
     * Set for first not seen email
     *
     * @param EntityManager $entityManager
     * @param $threadEmails
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
     * Reset head for thread
     *
     * @param EntityManager $entityManager
     * @param $threadEmails
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
     * @param EntityManager $entityManager
     */
    protected function setHeadFirstEmail(EntityManager $entityManager)
    {
        $email = end($threadEmails);
        $email->setHead(true);
        $entityManager->persist($email);
    }
}

<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
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
        if ($this->getQueue()) {
            $entityManager = $event->getEntityManager();
            $this->processEmailsHead($entityManager);
            $this->resetQueue();
            $entityManager->flush();
        }
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
                $thread = $this->emailThreadProvider->getEmailThread($entityManager, $entity);
                if ($thread) {
                    $entityManager->persist($thread);
                    $this->computeChanges($entityManager, $thread);
                    $entity->setThread($thread);
                }
                $this->updateRefs($entityManager, $entity);
                $this->addEmailToQueue($entity);
            }
        }
    }

    /**
     * Set heads of queued emails
     *
     * @param EntityManager $entityManager
     */
    protected function processEmailsHead(EntityManager $entityManager)
    {
        foreach ($this->getQueue() as $entity) {
            if ($entity instanceof Email) {
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
        if ($entity->getThread()) {
            /** @var Email $email */
            foreach ($this->emailThreadProvider->getEmailReferences($entityManager, $entity) as $email) {
                $email->setThread($entity->getThread());
                $entityManager->persist($email);
                $this->computeChanges($entityManager, $email);
                $this->addEmailToQueue($email);
            }
        }
    }

    /**
     * Update head entity for entity thread
     *
     * @param EntityManager $entityManager
     * @param Email $entity
     */
    protected function updateThreadHead(EntityManager $entityManager, Email $entity)
    {
        if ($entity->getThread() && $entity->getId()) {
            $threadEmails = $this->emailThreadProvider->getThreadEmails($entityManager, $entity);
            $this->resetHead($entityManager, $threadEmails);
            $this->setHeadLastEmail($entityManager, $threadEmails);
        }
    }

    /**
     * Set head for first  email
     *
     * @param EntityManager $entityManager
     * @param Email[] $threadEmails
     */
    protected function setHeadLastEmail(EntityManager $entityManager, $threadEmails)
    {
        $email = $threadEmails[0];
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
     * Reset queue
     */
    public function resetQueue()
    {
        $this->queue = [];
    }

    /**
     * @return Email[]
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Add email to queue
     *
     * @param Email $email
     */
    public function addEmailToQueue(Email $email)
    {
        $this->queue[] = $email;
    }

    /**
     * @param EntityManager $entityManager
     * @param $entity
     */
    protected function computeChanges(EntityManager $entityManager, $entity)
    {
        $uow = $entityManager->getUnitOfWork();
        $metaData = $entityManager->getClassMetadata(EmailThread::ENTITY_CLASS);
        $uow->computeChangeSet($metaData, $entity);
    }
}

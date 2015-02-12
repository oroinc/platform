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
}

<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;

class EntityListener
{
    /** @var EmailOwnerManager */
    protected $emailOwnerManager;

    /** @var EmailActivityManager */
    protected $emailActivityManager;

    /** @var EmailThreadManager */
    protected $emailThreadManager;

    /** @var Email[] */
    protected $emailsToRemove = [];

    /** @var array */
    protected $entitiesOwnedByEmail = [];

    /** @var EmailOwnersProvider */
    protected $emailOwnersProvider;

    /**
     * @param EmailOwnerManager $emailOwnerManager
     * @param EmailActivityManager $emailActivityManager
     * @param EmailThreadManager $emailThreadManager
     * @param EmailOwnersProvider $emailOwnersProvider
     */
    public function __construct(
        EmailOwnerManager    $emailOwnerManager,
        EmailActivityManager $emailActivityManager,
        EmailThreadManager   $emailThreadManager,
        EmailOwnersProvider  $emailOwnersProvider
    ) {
        $this->emailOwnerManager    = $emailOwnerManager;
        $this->emailActivityManager = $emailActivityManager;
        $this->emailThreadManager   = $emailThreadManager;
        $this->emailOwnersProvider  = $emailOwnersProvider;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $uow = $event->getEntityManager()->getUnitOfWork();
        $this->emailOwnerManager->handleOnFlush($event);
        $this->emailThreadManager->handleOnFlush($event);
        $this->emailActivityManager->handleOnFlush($event);

        $this->addNewEntityOwnedByEmail($uow->getScheduledEntityInsertions());
    }

    /**
     * @param PostFlushEventArgs $event
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $this->emailThreadManager->handlePostFlush($event);
        $this->emailActivityManager->handlePostFlush($event);
        $this->addAssociationWithEmailActivity($event);

        if ($this->emailsToRemove) {
            $em = $event->getEntityManager();

            foreach ($this->emailsToRemove as $email) {
                $em->remove($email);
            }

            $this->emailsToRemove = [];
            $em->flush();
        }
    }

    /**
     * @param array $entities
     */
    protected function addNewEntityOwnedByEmail($entities)
    {
        if ($entities) {
            foreach ($entities as $entity) {
                if ($this->emailOwnersProvider->supportOwnerProvider($entity)) {
                    $this->entitiesOwnedByEmail[] = $entity;
                }
            }
        }
    }

    /**
     * @param PostFlushEventArgs $event
     */
    protected function addAssociationWithEmailActivity(PostFlushEventArgs $event)
    {
        if ($this->entitiesOwnedByEmail) {
            $em = $event->getEntityManager();
            foreach ($this->entitiesOwnedByEmail as $entity) {
                $emails = $this->emailOwnersProvider->getEmailsByOwnerEntity($entity);
                foreach ($emails as $email) {
                    $this->emailActivityManager->addAssociation($email, $entity);
                }
            }
            $this->entitiesOwnedByEmail = [];
            $em->flush();
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $emailUser = $args->getEntity();
        if ($emailUser instanceof EmailUser) {
            $email = $emailUser->getEmail();

            if ($email->getEmailUsers()->isEmpty()) {
                $this->emailsToRemove[] = $email;
            }
        }
    }
}

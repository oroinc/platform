<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\UserBundle\Entity\User;

class EntityListener
{
    /** @var EmailOwnerManager */
    protected $emailOwnerManager;

    /** @var EmailActivityManager */
    protected $emailActivityManager;

    /** @var EmailThreadManager */
    protected $emailThreadManager;

    /**
     * @param EmailOwnerManager    $emailOwnerManager
     * @param EmailActivityManager $emailActivityManager
     * @param EmailThreadManager   $emailThreadManager
     */
    public function __construct(
        EmailOwnerManager    $emailOwnerManager,
        EmailActivityManager $emailActivityManager,
        EmailThreadManager   $emailThreadManager
    ) {
        $this->emailOwnerManager    = $emailOwnerManager;
        $this->emailActivityManager = $emailActivityManager;
        $this->emailThreadManager   = $emailThreadManager;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $this->emailOwnerManager->handleOnFlush($event);
        $this->emailThreadManager->handleOnFlush($event);
        $this->emailActivityManager->handleOnFlush($event);
    }

    /**
     * @param PostFlushEventArgs $event
     */
    public function postFlush(PostFlushEventArgs $event)
    {
        $this->emailThreadManager->handlePostFlush($event);
        $this->emailActivityManager->handlePostFlush($event);
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $emailUser = $eventArgs->getObject();

        if ($emailUser instanceof EmailUser
            && $emailUser->getOwner() == null
            && $emailUser->getOrganization() == null
        ) {
            $em = $eventArgs->getObjectManager();

            $origin = $emailUser->getFolder()->getOrigin();

            $qb = $em->getRepository('Oro\Bundle\UserBundle\Entity\User')
                ->createQueryBuilder('u')
                ->select('u')
                ->innerJoin('u.emailOrigins', 'o')
                ->where('o.id = :originId')
                ->setParameter('originId', $origin->getId())
                ->setMaxResults(1);

            /** @var User $user */
            $user = $qb->getQuery()->getSingleResult();
            $organizations = $user->getOrganizations();

            $length = sizeof($organizations);
            for ($i = 0; $i < $length; $i++) {
                $organization = $organizations[$i];

                if ($i == 0) {
                    $emailUser->setOwner($user);
                    $emailUser->setOrganization($organization);
                } else {
                    $eu = clone $emailUser;
                    $eu->setOwner($user);
                    $eu->setOrganization($organization);

                    $em->persist($eu);
                }
            }
        }
    }
}

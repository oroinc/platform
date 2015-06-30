<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\SecurityContext;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Manager\EmailFlagManager;

/**
 * Class EmailManager
 * @package Oro\Bundle\EmailBundle\Entity\Manager
 */
class EmailManager
{
    /**
     * @var EmailThreadManager
     */
    protected $emailThreadManager;

    /**
     * @var EmailThreadProvider
     */
    protected $emailThreadProvider;

    /** @var EntityManager */
    protected $em;

    /**
     * @var EmailFlagManager
     */
    protected $emailFlagManager;

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * Constructor
     *
     * @param EntityManager       $em                  - Entity Manager
     * @param EmailThreadManager  $emailThreadManager  - Email Thread Manager
     * @param EmailThreadProvider $emailThreadProvider - Email Thread Provider
     * @param EmailFlagManager    $emailFlagManager    - Email Flag Manager
     * @param SecurityContext     $securityContext     - Security Context
     */
    public function __construct(
        EntityManager $em,
        EmailThreadManager $emailThreadManager,
        EmailThreadProvider $emailThreadProvider,
        EmailFlagManager $emailFlagManager,
        SecurityContext $securityContext
    ) {
        $this->em = $em;
        $this->emailThreadManager = $emailThreadManager;
        $this->emailThreadProvider = $emailThreadProvider;
        $this->emailFlagManager = $emailFlagManager;
        $this->securityContext = $securityContext;
    }

    /**
     * Set email seen status
     *
     * @param EmailUser $entity - entity
     * @param bool      $value  - value for value filed EmailUser entity
     * @param bool      $flush  - if $flush is true then method executes flush
     *
     * @return void
     */
    public function setEmailUserSeen(EmailUser $entity, $value = true, $flush = false)
    {
        if ($entity->isSeen() !== $value) {
            $this->emailFlagManager->changeStatusSeen($entity, $value);
            $entity->setSeen($value);
            if ($flush) {
                $this->em->flush();
            }
        }
    }

    /**
     * @param Email $entity
     */
    public function setSeenStatus(Email $entity)
    {
        $emailUser = $this->getCurrentEmailUser($entity);

        if ($emailUser) {
            $this->setEmailUserSeen($emailUser, true, true);
        }
    }

    /**
     * Toggle user email seen
     *
     * @param EmailUser $entity
     */
    public function toggleEmailUserSeen(EmailUser $entity)
    {
        $seen = !((bool) $entity->isSeen());
        $this->setEmailUserSeen($entity, $seen);
        $this->em->persist($entity);

        if ($entity->getEmail()->getThread() && $entity->getOwner()) {
            $threadedEmailUserBuilder = $this
                ->em
                ->getRepository('OroEmailBundle:EmailUser')
                ->getEmailUserByThreadId([$entity->getEmail()->getThread()->getId()], $entity->getOwner());

            $threadedEmailUserList = $threadedEmailUserBuilder->getQuery()->getResult();
            foreach ($threadedEmailUserList as $threadedEmailUser) {
                $this->setEmailUserSeen($threadedEmailUser, $seen);
                $this->em->persist($threadedEmailUser);
            }
        }

        $this->em->flush();
    }

    /**
     * @param Email $entity
     * @param $target
     */
    public function addContextToEmailThread(Email $entity, $target)
    {
        $relatedEmails = $this->emailThreadProvider->getThreadEmails($this->em, $entity);
        foreach ($relatedEmails as $relatedEmail) {
            $relatedEmail->addActivityTarget($target);
        }
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * @param Email $entity
     * @param $target
     */
    public function deleteContextFromEmailThread(Email $entity, $target)
    {
        $relatedEmails = $this->emailThreadProvider->getThreadEmails($this->em, $entity);
        foreach ($relatedEmails as $relatedEmail) {
            $relatedEmail->removeActivityTarget($target);
        }
        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * Find EmilUser User logged in system
     *
     * @param Email $entity - entity Email
     *
     * @return null|EmailUser
     */
    protected function getCurrentEmailUser(Email $entity)
    {
        $user = $this->securityContext->getToken()->getUser();
        $emailUser = $this->em->getRepository('OroEmailBundle:EmailUser')
            ->findByEmailAndOwner($entity, $user);

        return $emailUser;
    }
}

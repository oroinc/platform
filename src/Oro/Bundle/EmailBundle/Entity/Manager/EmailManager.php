<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;

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
     * @param EntityManager $em
     * @param EmailThreadManager $emailThreadManager
     * @param EmailThreadProvider $emailThreadProvider
     */
    public function __construct(
        EntityManager $em,
        EmailThreadManager $emailThreadManager,
        EmailThreadProvider $emailThreadProvider
    ) {
        $this->em = $em;
        $this->emailThreadManager = $emailThreadManager;
        $this->emailThreadProvider = $emailThreadProvider;
    }

    /**
     * Set email seen status
     *
     * @param EmailUser $entity
     * @param bool $value
     */
    public function setEmailUserSeen(EmailUser $entity, $value = true, $flush = false)
    {
        if ($entity->isSeen() !== $value) {
            $entity->setSeen($value);
            $entity->setChangedStatusAt(new \DateTime('now', new \DateTimeZone('UTC')));
            if ($flush) {
                $this->em->flush();
            }
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
}

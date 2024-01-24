<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;

/**
 * Provides a set of methods to manage data related to email threads.
 */
class EmailThreadManager
{
    /** @var EmailThreadProvider */
    protected $emailThreadProvider;

    /** @var EntityManager */
    protected $em;

    public function __construct(EmailThreadProvider $emailThreadProvider, EntityManager $em)
    {
        $this->emailThreadProvider = $emailThreadProvider;
        $this->em = $em;
    }

    /**
     * @param Email[] $newEmails
     */
    public function updateThreads(array $newEmails)
    {
        foreach ($newEmails as $email) {
            $threadEmails = $this->findThreadEmails($this->em, $email);
            $thread = $this->findThread($threadEmails);
            if (null === $thread && $threadEmails) {
                $thread = new EmailThread();
                $this->em->persist($thread);
            }
            if (null !== $thread) {
                $email->setThread($thread);
                foreach ($threadEmails as $threadEmail) {
                    if (null === $threadEmail->getThread()) {
                        $threadEmail->setThread($thread);
                    }
                }
            }
        }
    }

    /**
     * @param Email[] $updatedEmails
     */
    public function updateHeads(array $updatedEmails)
    {
        foreach ($updatedEmails as $entity) {
            if (!$entity->getThread() || !$entity->getId()) {
                continue;
            }

            $threadEmails = $this->emailThreadProvider->getThreadEmails($this->em, $entity);
            if (count($threadEmails) === 0) {
                continue;
            }

            /** @var Email $email */
            foreach ($threadEmails as $email) {
                $email->setHead(false);
                $this->em->persist($email);
            }
            $email = $threadEmails[0];
            $email->setHead(true);
            $this->em->persist($email);
        }
    }

    /**
     * Updates email references' threadId
     */
    protected function updateRefs(EntityManager $entityManager, Email $entity)
    {
        if ($entity->getThread()) {
            /** @var Email $email */
            foreach ($this->emailThreadProvider->getEmailReferences($this->em, $entity) as $email) {
                if (!$email->getThread()) {
                    $email->setThread($entity->getThread());
                    $entityManager->persist($email);
                }
            }
        }
    }


    /**
     * @param EntityManager $em
     * @param Email $email
     *
     * @return Email[]
     */
    private function findThreadEmails(EntityManager $em, Email $email): array
    {
        $threadEmails = $this->emailThreadProvider->getEmailReferences($em, $email);
        if (!$threadEmails) {
            $threadEmails = $this->emailThreadProvider->getReferredEmails($em, $email);
        }

        return $threadEmails;
    }

    /**
     * @param Email[] $threadEmails
     *
     * @return EmailThread|null
     */
    private function findThread(array $threadEmails): ?EmailThread
    {
        $thread = null;
        foreach ($threadEmails as $threadEmail) {
            $thread = $threadEmail->getThread();
            if (null !== $thread) {
                break;
            }
        }

        return $thread;
    }
}

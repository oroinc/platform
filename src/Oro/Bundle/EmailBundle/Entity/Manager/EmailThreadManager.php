<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;

class EmailThreadManager
{
    /** @var EmailThreadProvider */
    protected $emailThreadProvider;

    /** @var EntityManager */
    protected $em;

    /**
     * @param EmailThreadProvider $emailThreadProvider
     * @param EntityManager $em
     */
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
        foreach ($newEmails as $entity) {
            $thread = $this->emailThreadProvider->getEmailThread($this->em, $entity);
            if ($thread) {
                $this->em->persist($thread);
                $entity->setThread($thread);
            }
            $this->updateRefs($this->em, $entity);
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
     *
     * @param EntityManager $entityManager
     * @param Email $entity
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
}

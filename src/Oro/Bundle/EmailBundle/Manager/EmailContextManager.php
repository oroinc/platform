<?php

namespace Oro\Bundle\EmailBundle\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;

class EmailContextManager
{
    /** @var EntityManager */
    protected $em;

    /** @var EmailActivityListProvider */
    protected $activityListProvider;

    /** @var EmailActivityManager */
    protected $emailActivityManager;

    /**
     * @param EntityManager $em
     * @param EmailActivityListProvider $activityListProvider
     * @param EmailActivityManager $emailActivityManager
     */
    public function __construct(
        EntityManager $em,
        EmailActivityListProvider $activityListProvider,
        EmailActivityManager $emailActivityManager
    ) {
        $this->em = $em;
        $this->activityListProvider = $activityListProvider;
        $this->emailActivityManager = $emailActivityManager;
    }

    /**
     * @param Email $email
     */
    public function addContextsToThread(Email $email)
    {
        $thread = $email->getThread();
        if ($thread) {
            $relatedEmails = $this->em->getRepository(Email::ENTITY_CLASS)->findByThread($thread);
            $contexts = $this->activityListProvider->getTargetEntities($email);
            foreach ($relatedEmails as $relatedEmail) {
                if ($email->getId() !== $relatedEmail->getId()) {
                    $oldContexts = $this->activityListProvider->getTargetEntities($relatedEmail);
                    foreach ($oldContexts as $context) {
                        $this->emailActivityManager->removeActivityTarget($relatedEmail, $context);
                    }
                    foreach ($contexts as $context) {
                        $this->emailActivityManager->addAssociation($relatedEmail, $context);
                    }
                    $this->em->persist($relatedEmail);
                }
            }
        }
    }

    /**
     * @param Email $email
     */
    public function addContextsToNewEmail(Email $email)
    {
//        $thread = $email->getThread();
//        if ($thread) {
//            $relatedEmails = $this->em->getRepository(Email::ENTITY_CLASS)->findByThread($thread);
//            $contexts = $this->activityListProvider->getTargetEntities($email);
//            foreach ($relatedEmails as $relatedEmail) {
//                if ($email->getId() !== $relatedEmail->getId()) {
//                    $oldContexts = $this->activityListProvider->getTargetEntities($relatedEmail);
//                    foreach ($oldContexts as $context) {
//                        $this->emailActivityManager->removeActivityTarget($relatedEmail, $context);
//                    }
//                    foreach ($contexts as $context) {
//                        $this->emailActivityManager->addAssociation($relatedEmail, $context);
//                    }
//                    $this->em->persist($relatedEmail);
//                }
//            }
//        }
    }
}

<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * MQ processor that recalculates the visibility of email users where the email address is used.
 */
class RecalculateEmailVisibilityProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ManagerRegistry $doctrine;
    private EmailAddressVisibilityManager $emailAddressVisibilityManager;
    private ActivityListChainProvider $activityListProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        EmailAddressVisibilityManager $emailAddressVisibilityManager,
        ActivityListChainProvider $activityListProvider
    ) {
        $this->doctrine = $doctrine;
        $this->emailAddressVisibilityManager = $emailAddressVisibilityManager;
        $this->activityListProvider = $activityListProvider;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [RecalculateEmailVisibilityTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManagerForClass(Email::class);
        $publicEmailUserIds = [];
        /** @var Email[] $emails */
        $emails = $this->doctrine->getRepository(Email::class)->getEmailsByEmailAddress($data['email']);
        foreach ($emails as $email) {
            foreach ($email->getEmailUsers() as $emailUser) {
                $this->emailAddressVisibilityManager->processEmailUserVisibility($emailUser);
                if (!$emailUser->isEmailPrivate()) {
                    $publicEmailUserIds[] = $emailUser->getId();
                }
            }
        }
        $entityManager->flush();

        if ($publicEmailUserIds) {
            $this->processPublicEmailUsers($publicEmailUserIds, $entityManager);
        }

        return self::ACK;
    }

    /**
     * Adds activity lists for emails that become public.
     *
     * @param int[]                  $publicEmailUserIds
     * @param EntityManagerInterface $entityManager
     */
    private function processPublicEmailUsers(array $publicEmailUserIds, EntityManagerInterface $entityManager): void
    {
        $hasChanges = false;
        foreach ($publicEmailUserIds as $emailUserId) {
            $emailUser = $entityManager->find(EmailUser::class, $emailUserId);
            $activityList = $this->activityListProvider->getActivityListByEntity($emailUser, $entityManager);
            if (null !== $activityList) {
                continue;
            }

            $email = $emailUser->getEmail();
            $activityList = $this->activityListProvider->getNewActivityList($email);
            if (null === $activityList) {
                continue;
            }

            $activityListProvider = $this->activityListProvider->getProviderForEntity($email);
            $newActivityOwners = $activityListProvider->getActivityOwners($email, $activityList);
            foreach ($newActivityOwners as $newOwner) {
                $activityList->addActivityOwner($newOwner);
            }
            if ($activityListProvider->isActivityListApplicable($activityList)) {
                $entityManager->persist($activityList);
                $hasChanges = true;
            }
        }
        if ($hasChanges) {
            $entityManager->flush();
        }
    }
}

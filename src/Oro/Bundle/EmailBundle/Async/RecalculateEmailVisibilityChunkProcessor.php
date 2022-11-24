<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityChunkTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * MQ processor that recalculates the visibility of email users by given ids.
 */
class RecalculateEmailVisibilityChunkProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ManagerRegistry $doctrine;
    private EmailAddressVisibilityManager $emailAddressVisibilityManager;
    private ActivityListChainProvider $activityListProvider;
    private JobRunner $jobRunner;

    public function __construct(
        ManagerRegistry $doctrine,
        EmailAddressVisibilityManager $emailAddressVisibilityManager,
        ActivityListChainProvider $activityListProvider,
        JobRunner $jobRunner
    ) {
        $this->doctrine = $doctrine;
        $this->emailAddressVisibilityManager = $emailAddressVisibilityManager;
        $this->activityListProvider = $activityListProvider;
        $this->jobRunner = $jobRunner;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [RecalculateEmailVisibilityChunkTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $message->getBody();

        $result = $this->jobRunner->runDelayed(
            $data['jobId'],
            function () use ($data) {
                /** @var EntityManagerInterface $entityManager */
                $entityManager = $this->doctrine->getManagerForClass(Email::class);
                $publicEmailUserIds = [];
                $emailUsers = $this->doctrine->getRepository(EmailUser::class)->findBy(['id' => $data['ids']]);
                foreach ($emailUsers as $emailUser) {
                    $this->emailAddressVisibilityManager->processEmailUserVisibility($emailUser);
                    if (!$emailUser->isEmailPrivate()) {
                        $publicEmailUserIds[] = $emailUser->getId();
                    }
                }
                $entityManager->flush();

                if ($publicEmailUserIds) {
                    $this->processPublicEmailUsers($publicEmailUserIds, $entityManager);
                }
                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
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

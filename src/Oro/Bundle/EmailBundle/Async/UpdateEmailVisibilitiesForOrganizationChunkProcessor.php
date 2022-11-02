<?php

namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailVisibilitiesForOrganizationChunkTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Updates visibilities for specific emails for a specific organization.
 */
class UpdateEmailVisibilitiesForOrganizationChunkProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface
{
    private const BUFFER_SIZE = 100;

    private ManagerRegistry $doctrine;
    private EmailAddressVisibilityManager $emailAddressVisibilityManager;
    private JobRunner $jobRunner;

    public function __construct(
        ManagerRegistry $doctrine,
        EmailAddressVisibilityManager $emailAddressVisibilityManager,
        JobRunner $jobRunner
    ) {
        $this->doctrine = $doctrine;
        $this->emailAddressVisibilityManager = $emailAddressVisibilityManager;
        $this->jobRunner = $jobRunner;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [UpdateEmailVisibilitiesForOrganizationChunkTopic::getName()];
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
                $this->processJob($data['organizationId'], $data['firstEmailId'], $data['lastEmailId'] ?? null);

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    private function processJob(
        int $organizationId,
        int $firstEmailId,
        ?int $lastEmailId
    ): void {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Email::class);
        $emailUsers = $this->getEmailUsers($em, $organizationId, $firstEmailId, $lastEmailId);
        foreach ($emailUsers as $emailUser) {
            $this->emailAddressVisibilityManager->processEmailUserVisibility($emailUser);
        }
        $em->flush();
    }

    private function getEmailUsers(
        EntityManagerInterface $em,
        int $organizationId,
        int $firstEmailId,
        ?int $lastEmailId
    ): BufferedQueryResultIteratorInterface {
        $qb = $em->createQueryBuilder()
            ->from(EmailUser::class, 'eu')
            ->select('eu, euo, e, efa, er, era')
            ->join('eu.organization', 'euo')
            ->join('eu.email', 'e')
            ->leftJoin('e.fromEmailAddress', 'efa')
            ->leftJoin('e.recipients', 'er')
            ->leftJoin('er.emailAddress', 'era')
            ->where('euo.id = :organizationId AND e.id >= :firstEmailId')
            ->setParameter('organizationId', $organizationId)
            ->setParameter('firstEmailId', $firstEmailId)
            ->orderBy('e.id');
        if (null !== $lastEmailId) {
            $qb->andWhere('e.id <= :lastEmailId')
                ->setParameter('lastEmailId', $lastEmailId);
        }

        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setBufferSize(self::BUFFER_SIZE);

        return $iterator;
    }
}

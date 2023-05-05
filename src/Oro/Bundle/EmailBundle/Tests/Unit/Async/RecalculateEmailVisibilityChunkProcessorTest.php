<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EmailBundle\Async\RecalculateEmailVisibilityChunkProcessor;
use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityChunkTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ReflectionUtil;

class RecalculateEmailVisibilityChunkProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EmailRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var EmailAddressVisibilityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressVisibilityManager;

    /** @var ActivityListChainProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $activityListProvider;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var RecalculateEmailVisibilityChunkProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EmailRepository::class);
        $this->emailAddressVisibilityManager = $this->createMock(EmailAddressVisibilityManager::class);
        $this->activityListProvider = $this->createMock(ActivityListChainProvider::class);
        $this->jobRunner = $this->createMock(JobRunner::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Email::class)
            ->willReturn($this->em);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(EmailUser::class)
            ->willReturn($this->repository);

        $this->processor = new RecalculateEmailVisibilityChunkProcessor(
            $doctrine,
            $this->emailAddressVisibilityManager,
            $this->activityListProvider,
            $this->jobRunner
        );
    }

    private function expectsRunDelayed(int $jobId): void
    {
        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function ($jobId, $runCallback) {
                return $runCallback($this->jobRunner, new Job());
            });
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    private function getEmailUser(bool $private): EmailUser
    {
        $emailUser = new EmailUser();
        ReflectionUtil::setId($emailUser, 123);
        $emailUser->setIsEmailPrivate($private);

        $email = new Email();
        $email->addEmailUser($emailUser);

        return $emailUser;
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [RecalculateEmailVisibilityChunkTopic::getName()],
            RecalculateEmailVisibilityChunkProcessor::getSubscribedTopics()
        );
    }

    public function testProcessForPrivateEmail(): void
    {
        $jobId = 123;
        $ids = [12];
        $message = $this->getMessage([
            'jobId' => $jobId,
            'ids'   => $ids
        ]);

        $emailUser = $this->getEmailUser(true);

        $this->repository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [12]])
            ->willReturn([$emailUser]);

        $this->emailAddressVisibilityManager->expects(self::once())
            ->method('processEmailUserVisibility')
            ->with(self::identicalTo($emailUser));

        $this->em->expects(self::once())
            ->method('flush');

        $this->expectsRunDelayed($jobId);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessForPublicEmailThatAlreadyExistsInActivityList(): void
    {
        $jobId = 245;
        $ids = [290];
        $message = $this->getMessage([
            'jobId' => $jobId,
            'ids'   => $ids
        ]);

        $emailUser = $this->getEmailUser(false);

        $this->repository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [290]])
            ->willReturn([$emailUser]);

        $this->emailAddressVisibilityManager->expects(self::once())
            ->method('processEmailUserVisibility')
            ->with(self::identicalTo($emailUser));

        $this->em->expects(self::once())
            ->method('find')
            ->with(EmailUser::class, $emailUser->getId())
            ->willReturn($emailUser);
        $this->activityListProvider->expects(self::once())
            ->method('getActivityListByEntity')
            ->with(self::identicalTo($emailUser), self::identicalTo($this->em))
            ->willReturn($this->createMock(ActivityList::class));

        $this->em->expects(self::once())
            ->method('flush');

        $this->expectsRunDelayed($jobId);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessForPublicEmailForWhichNewActivityListIsNotApplicable(): void
    {
        $jobId = 23;
        $ids = [134];
        $message = $this->getMessage([
            'jobId' => $jobId,
            'ids'   => $ids
        ]);

        $emailUser = $this->getEmailUser(false);
        $activityList = new ActivityList();

        $this->repository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [134]])
            ->willReturn([$emailUser]);

        $this->emailAddressVisibilityManager->expects(self::once())
            ->method('processEmailUserVisibility')
            ->with(self::identicalTo($emailUser));

        $this->em->expects(self::once())
            ->method('find')
            ->with(EmailUser::class, $emailUser->getId())
            ->willReturn($emailUser);
        $this->activityListProvider->expects(self::once())
            ->method('getActivityListByEntity')
            ->with(self::identicalTo($emailUser), self::identicalTo($this->em))
            ->willReturn(null);
        $this->activityListProvider->expects(self::once())
            ->method('getNewActivityList')
            ->with(self::identicalTo($emailUser->getEmail()))
            ->willReturn($activityList);

        $entityActivityListProvider = $this->createMock(ActivityListProviderInterface::class);
        $this->activityListProvider->expects(self::once())
            ->method('getProviderForEntity')
            ->with(self::identicalTo($emailUser->getEmail()))
            ->willReturn($entityActivityListProvider);
        $entityActivityListProvider->expects(self::once())
            ->method('getActivityOwners')
            ->with(self::identicalTo($emailUser->getEmail()), self::identicalTo($activityList))
            ->willReturn([]);
        $entityActivityListProvider->expects(self::once())
            ->method('isActivityListApplicable')
            ->with(self::identicalTo($activityList))
            ->willReturn(false);

        $this->em->expects(self::never())
            ->method('persist');
        $this->em->expects(self::once())
            ->method('flush');

        $this->expectsRunDelayed($jobId);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessForPublicEmailThatShouldBeAddedToNewActivityList(): void
    {
        $jobId = 444;
        $ids = [333];
        $message = $this->getMessage([
            'jobId' => $jobId,
            'ids'   => $ids
        ]);

        $emailUser = $this->getEmailUser(false);
        $activityList = new ActivityList();
        $activityOwner = $this->createMock(ActivityOwner::class);

        $this->repository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [333]])
            ->willReturn([$emailUser]);

        $this->emailAddressVisibilityManager->expects(self::once())
            ->method('processEmailUserVisibility')
            ->with(self::identicalTo($emailUser));

        $this->em->expects(self::once())
            ->method('find')
            ->with(EmailUser::class, $emailUser->getId())
            ->willReturn($emailUser);
        $this->activityListProvider->expects(self::once())
            ->method('getActivityListByEntity')
            ->with(self::identicalTo($emailUser), self::identicalTo($this->em))
            ->willReturn(null);
        $this->activityListProvider->expects(self::once())
            ->method('getNewActivityList')
            ->with(self::identicalTo($emailUser->getEmail()))
            ->willReturn($activityList);

        $entityActivityListProvider = $this->createMock(ActivityListProviderInterface::class);
        $this->activityListProvider->expects(self::once())
            ->method('getProviderForEntity')
            ->with(self::identicalTo($emailUser->getEmail()))
            ->willReturn($entityActivityListProvider);
        $entityActivityListProvider->expects(self::once())
            ->method('getActivityOwners')
            ->with(self::identicalTo($emailUser->getEmail()), self::identicalTo($activityList))
            ->willReturn([$activityOwner]);
        $entityActivityListProvider->expects(self::once())
            ->method('isActivityListApplicable')
            ->with(self::identicalTo($activityList))
            ->willReturn(true);

        $this->em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($activityList))
            ->willReturnCallback(function (ActivityList $al) use ($activityOwner) {
                self::assertSame($activityOwner, $al->getActivityOwners()[0]);
            });
        $this->em->expects(self::exactly(2))
            ->method('flush');

        $this->expectsRunDelayed($jobId);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }
}

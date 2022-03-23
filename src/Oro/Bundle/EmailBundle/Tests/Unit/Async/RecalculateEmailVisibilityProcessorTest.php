<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\TestActivityProvider;
use Oro\Bundle\EmailBundle\Async\RecalculateEmailVisibilityProcessor;
use Oro\Bundle\EmailBundle\Async\Topic\RecalculateEmailVisibilityTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ReflectionUtil;

class RecalculateEmailVisibilityProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EmailRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var EmailAddressVisibilityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressVisibilityManager;

    /** @var ActivityListChainProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $activityListProvider;

    /** @var RecalculateEmailVisibilityProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->repository = $this->createMock(EmailRepository::class);
        $this->emailAddressVisibilityManager = $this->createMock(EmailAddressVisibilityManager::class);
        $this->activityListProvider = $this->createMock(ActivityListChainProvider::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Email::class)
            ->willReturn($this->em);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Email::class)
            ->willReturn($this->repository);

        $this->processor = new RecalculateEmailVisibilityProcessor(
            $doctrine,
            $this->emailAddressVisibilityManager,
            $this->activityListProvider
        );
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
            [RecalculateEmailVisibilityTopic::getName()],
            RecalculateEmailVisibilityProcessor::getSubscribedTopics()
        );
    }

    public function testProcessForPrivateEmail(): void
    {
        $emailAddress = 'test@test.com';

        $emailUser = $this->getEmailUser(true);

        $this->repository->expects(self::once())
            ->method('getEmailsByEmailAddress')
            ->with($emailAddress)
            ->willReturn([$emailUser->getEmail()]);

        $this->emailAddressVisibilityManager->expects(self::once())
            ->method('processEmailUserVisibility')
            ->with(self::identicalTo($emailUser));

        $this->em->expects(self::once())
            ->method('flush');

        $result = $this->processor->process(
            $this->getMessage(['email' => $emailAddress]),
            $this->createMock(SessionInterface::class)
        );
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessForPublicEmailThatAlreadyExistsInActivityList(): void
    {
        $emailAddress = 'test@test.com';

        $emailUser = $this->getEmailUser(false);

        $this->repository->expects(self::once())
            ->method('getEmailsByEmailAddress')
            ->with($emailAddress)
            ->willReturn([$emailUser->getEmail()]);

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

        $result = $this->processor->process(
            $this->getMessage(['email' => $emailAddress]),
            $this->createMock(SessionInterface::class)
        );
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessForPublicEmailForWhichNewActivityListIsNotApplicable(): void
    {
        $emailAddress = 'test@test.com';

        $emailUser = $this->getEmailUser(false);
        $activityList = new ActivityList();

        $this->repository->expects(self::once())
            ->method('getEmailsByEmailAddress')
            ->with($emailAddress)
            ->willReturn([$emailUser->getEmail()]);

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

        $entityActivityListProvider = $this->createMock(TestActivityProvider::class);
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

        $result = $this->processor->process(
            $this->getMessage(['email' => $emailAddress]),
            $this->createMock(SessionInterface::class)
        );
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessForPublicEmailThatShouldBeAddedToNewActivityList(): void
    {
        $emailAddress = 'test@test.com';

        $emailUser = $this->getEmailUser(false);
        $activityList = new ActivityList();
        $activityOwner = $this->createMock(ActivityOwner::class);

        $this->repository->expects(self::once())
            ->method('getEmailsByEmailAddress')
            ->with($emailAddress)
            ->willReturn([$emailUser->getEmail()]);

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

        $entityActivityListProvider = $this->createMock(TestActivityProvider::class);
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

        $result = $this->processor->process(
            $this->getMessage(['email' => $emailAddress]),
            $this->createMock(SessionInterface::class)
        );
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }
}

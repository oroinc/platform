<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\EventListener\EmailBodyAddListener;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EmailBodyAddListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var EmailAttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAttachmentManager;

    /** @var EmailActivityListProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $activityListProvider;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var ActivityListChainProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $chainProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EmailBodyAddListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->emailAttachmentManager = $this->createMock(EmailAttachmentManager::class);
        $this->activityListProvider = $this->createMock(EmailActivityListProvider::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->chainProvider = $this->createMock(ActivityListChainProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new EmailBodyAddListener(
            $this->emailAttachmentManager,
            $this->configProvider,
            $this->activityListProvider,
            $this->authorizationChecker,
            $this->tokenStorage,
            $this->chainProvider,
            $this->doctrine
        );
    }

    public function testLinkToScopeWhenAccessToAttachmentsIsNotGranted(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Attachment::class)
            ->willReturn(false);
        $this->activityListProvider->expects(self::never())
            ->method('getTargetEntities')
            ->willReturn([new SomeEntity()]);

        $this->listener->linkToScope(new EmailBodyAdded($this->createMock(Email::class)));
    }

    /**
     * @dataProvider linkToScopeDataProvider
     */
    public function testLinkToScope(int|bool $config, int $managerCalls, int $attachmentCalls): void
    {
        $attachments = $this->createMock(EmailAttachment::class);
        $emailBody = $this->createMock(EmailBody::class);
        $email = $this->createMock(Email::class);
        $configInterface = $this->createMock(ConfigInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Attachment::class)
            ->willReturn(true);
        $this->activityListProvider->expects(self::once())
            ->method('getTargetEntities')
            ->willReturn([new SomeEntity()]);

        $configInterface->expects(self::once())
            ->method('get')
            ->willReturn($config);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($configInterface);

        $this->emailAttachmentManager->expects(self::exactly($managerCalls))
            ->method('linkEmailAttachmentToTargetEntity');
        $emailBody->expects(self::exactly($attachmentCalls))
            ->method('getAttachments')
            ->willReturn([$attachments]);
        $email->expects(self::exactly($attachmentCalls))
            ->method('getEmailBody')
            ->willReturn($emailBody);

        $this->listener->linkToScope(new EmailBodyAdded($email));
    }

    public function linkToScopeDataProvider(): array
    {
        return [
            'link to scope if number true' => [
                'config' => 1,
                'managerCalls' => 1,
                'attachmentCalls' => 1
            ],
            'do not link to scope number false' => [
                'config' => 0,
                'managerCalls' => 0,
                'attachmentCalls' => 0
            ],
            'link to scope if true' => [
                'config' => true,
                'managerCalls' => 1,
                'attachmentCalls' => 1
            ],
            'do not link to scope if false' => [
                'config' => false,
                'managerCalls' => 0,
                'attachmentCalls' => 0
            ]
        ];
    }

    public function testUpdateActivityDescription(): void
    {
        $email = $this->createMock(Email::class);
        $activityList = $this->createMock(ActivityList::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Email::class)
            ->willReturn($em);

        $this->chainProvider->expects(self::once())
            ->method('getUpdatedActivityList')
            ->with(self::identicalTo($email), self::identicalTo($em))
            ->willReturn($activityList);

        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('persist')
            ->with($activityList);
        $em->expects(self::once())
            ->method('flush');
        $em->expects(self::once())
            ->method('commit');
        $em->expects(self::never())
            ->method('rollback');

        $this->listener->updateActivityDescription(new EmailBodyAdded($email));
    }

    public function testUpdateActivityDescriptionWhenItIsFailed(): void
    {
        $exception = new \RuntimeException('some error');
        $this->expectExceptionObject($exception);

        $email = $this->createMock(Email::class);
        $activityList = $this->createMock(ActivityList::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Email::class)
            ->willReturn($em);

        $this->chainProvider->expects(self::once())
            ->method('getUpdatedActivityList')
            ->with(self::identicalTo($email), self::identicalTo($em))
            ->willReturn($activityList);

        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('persist')
            ->with($activityList);
        $em->expects(self::once())
            ->method('flush')
            ->willThrowException($exception);
        $em->expects(self::never())
            ->method('commit');
        $em->expects(self::once())
            ->method('rollback');

        $this->listener->updateActivityDescription(new EmailBodyAdded($email));
    }
}

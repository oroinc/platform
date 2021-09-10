<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EmailBodyAddListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailBodyAddListener */
    private $listener;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var EmailAttachmentManager */
    private $emailAttachmentManager;

    /** @var EmailActivityListProvider */
    private $activityListProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var ActivityListChainProvider */
    private $chainProvider;

    /** @var EntityManager */
    private $entityManager;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->emailAttachmentManager = $this->createMock(EmailAttachmentManager::class);
        $this->activityListProvider = $this->createMock(EmailActivityListProvider::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->chainProvider = $this->createMock(ActivityListChainProvider::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->listener = new EmailBodyAddListener(
            $this->emailAttachmentManager,
            $this->configProvider,
            $this->activityListProvider,
            $this->authorizationChecker,
            $this->tokenStorage,
            $this->chainProvider,
            $this->entityManager
        );
    }

    public function testLinkToScopeIsNotGranted()
    {
        $event = $this->createMock(EmailBodyAdded::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(1);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);
        $this->activityListProvider->expects($this->never())
            ->method('getTargetEntities')
            ->willReturn([new SomeEntity()]);

        $this->listener->linkToScope($event);
    }

    /**
     * @dataProvider getTestData
     */
    public function testLinkToScope($config, $managerCalls, $attachmentCalls)
    {
        $attachments = $this->createMock(EmailAttachment::class);
        $emailBody = $this->createMock(EmailBody::class);
        $email = $this->createMock(Email::class);
        $event = $this->createMock(EmailBodyAdded::class);
        $configInterface = $this->createMock(ConfigInterface::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(1);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);
        $this->activityListProvider->expects($this->once())
            ->method('getTargetEntities')
            ->willReturn([new SomeEntity()]);

        $configInterface->expects($this->once())
            ->method('get')
            ->willReturn($config);
        $this->configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($configInterface);

        $this->emailAttachmentManager
            ->expects($this->exactly($managerCalls))
            ->method('linkEmailAttachmentToTargetEntity');
        $emailBody->expects($this->exactly($attachmentCalls))
            ->method('getAttachments')
            ->willReturn([$attachments]);
        $email->expects($this->exactly($attachmentCalls))
            ->method('getEmailBody')
            ->willReturn($emailBody);
        $event->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->listener->linkToScope($event);
    }

    public function testUpdateActivityDescription()
    {
        $activityList = $this->createMock(ActivityList::class);

        $event = $this->createMock(EmailBodyAdded::class);

        $email = $this->createMock(Email::class);

        $event->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->chainProvider->expects($this->once())
            ->method('getUpdatedActivityList')
            ->with($this->identicalTo($email), $this->identicalTo($this->entityManager))
            ->willReturn($activityList);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($activityList);

        $this->listener->updateActivityDescription($event);
    }

    public function getTestData()
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
}

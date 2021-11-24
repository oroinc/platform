<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyFailedException;
use Oro\Bundle\EmailBundle\Exception\SyncWithNotificationAlertException;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderSelector;
use Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer;
use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailEntity;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertInterface;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailBodySynchronizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var EmailBodyLoaderSelector|\PHPUnit\Framework\MockObject\MockObject */
    private $selector;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var NotificationAlertManager|\PHPUnit\Framework\MockObject\MockObject */
    private $notificationAlertManager;

    /** @var EmailBodySynchronizer */
    private $synchronizer;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->selector = $this->createMock(EmailBodyLoaderSelector::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->notificationAlertManager = $this->createMock(NotificationAlertManager::class);

        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->em);

        $this->synchronizer = new EmailBodySynchronizer(
            $this->selector,
            $this->doctrine,
            $this->dispatcher,
            $this->notificationAlertManager
        );
        $this->synchronizer->setLogger($this->logger);
    }

    public function testSyncOneEmailBodyForAlreadyCached()
    {
        $email = new Email();
        $email->setEmailBody(new EmailBody());

        $this->selector->expects($this->never())
            ->method('select');
        $this->notificationAlertManager->expects(self::never())
            ->method('addNotificationAlert');
        $this->notificationAlertManager->expects(self::never())
            ->method('resolveNotificationAlertsByAlertTypeForUserAndOrganization');
        $this->notificationAlertManager->expects(self::never())
            ->method('resolveNotificationAlertByItemIdForUserAndOrganization');

        $this->synchronizer->syncOneEmailBody($email);
    }

    public function testSyncOneEmailBody()
    {
        $user = new User();
        $user->setId(12);
        $organization = new Organization();
        $organization->setId(22);
        $email = new TestEmailEntity(125);
        $emailBody = new EmailBody();
        $emailUser = new EmailUser();

        $origin = new TestEmailOrigin();
        $origin->setActive(true);
        $origin->setOwner($user);
        $origin->setOrganization($organization);
        $folder = new EmailFolder();
        $folder->setOrigin($origin);
        $origin->addFolder($folder);
        $emailUser->setOrigin($origin);
        $emailUser->addFolder($folder);
        $email->addEmailUser($emailUser);

        $loader = $this->createMock(EmailBodyLoaderInterface::class);

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->willReturn($loader);
        $loader->expects($this->once())
            ->method('loadEmailBody')
            ->with(
                $this->identicalTo($folder),
                $this->identicalTo($email),
                $this->identicalTo($this->em)
            )
            ->willReturn($emailBody);

        $this->em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo($email));

        $this->logger->expects($this->never())
            ->method('notice');
        $this->logger->expects($this->never())
            ->method('warning');
        $this->notificationAlertManager->expects(self::never())
            ->method('addNotificationAlert');
        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertsByAlertTypeForUserAndOrganization');
        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertsByAlertTypeAndStepForUserAndOrganization');

        $this->synchronizer->syncOneEmailBody($email);

        $this->assertSame($emailBody, $email->getEmailBody());
    }

    public function testSyncOneEmailBodyFailure()
    {
        $this->expectException(LoadEmailBodyFailedException::class);
        $this->expectExceptionMessage('Cannot load a body for "test email" email.');

        $email = new Email();
        ReflectionUtil::setId($email, 123);
        $email->setSubject('test email');
        $emailBody = new EmailBody();
        $emailUser = new EmailUser();

        $user = new User();
        $user->setId(34);
        $organization = new Organization();
        $organization->setId(33);
        $origin = new TestEmailOrigin(456);
        $origin->setActive(true);
        $origin->setOwner($user);
        $origin->setOrganization($organization);
        $folder = new EmailFolder();
        $folder->setOrigin($origin);
        $origin->addFolder($folder);
        $emailUser->setOrigin($origin);
        $emailUser->addFolder($folder);
        $email->addEmailUser($emailUser);

        $exception = new \Exception('some exception');

        $loader = $this->createMock(EmailBodyLoaderInterface::class);

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->willReturn($loader);
        $loader->expects($this->once())
            ->method('loadEmailBody')
            ->willThrowException($exception);

        $this->em->expects($this->once())
            ->method('persist');
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info');
        $this->notificationAlertManager->expects(self::once())
            ->method('addNotificationAlert')
            ->willReturnCallback(function (NotificationAlertInterface $notificationAlert) {
                self::assertEquals(
                    'Load email body failed. Exception message:some exception',
                    $notificationAlert->toArray()['message']
                );

                return 'test_id';
            });
        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertsByAlertTypeForUserAndOrganization');
        $this->notificationAlertManager->expects(self::never())
            ->method('resolveNotificationAlertsByAlertTypeAndStepForUserAndOrganization');

        $this->synchronizer->syncOneEmailBody($email);

        $this->assertSame($emailBody, $email->getEmailBody());
    }

    public function testSyncOneEmailBodyNotFound()
    {
        $this->expectException(LoadEmailBodyFailedException::class);
        $this->expectExceptionMessage('Cannot load a body for "test email" email.');

        $email = new Email();
        ReflectionUtil::setId($email, 123);
        $email->setSubject('test email');
        $emailBody = new EmailBody();
        $emailUser = new EmailUser();

        $user = new User();
        $user->setId(43);
        $organization = new Organization();
        $organization->setId(44);
        $origin = new TestEmailOrigin(789);
        $origin->setActive(true);
        $origin->setOwner($user);
        $origin->setOrganization($organization);
        $folder = new EmailFolder();
        $folder->setOrigin($origin);
        $origin->addFolder($folder);
        $emailUser->setOrigin($origin);
        $emailUser->addFolder($folder);
        $email->addEmailUser($emailUser);

        $exception = new EmailBodyNotFoundException($email);

        $loader = $this->createMock(EmailBodyLoaderInterface::class);

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->willReturn($loader);
        $loader->expects($this->once())
            ->method('loadEmailBody')
            ->willThrowException($exception);

        $this->em->expects($this->once())
            ->method('persist');
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('notice')
            ->with(
                'Attempt to load email body from remote server failed. Email id: 123.'
                . ' Error: Cannot find a body for "test email" email.',
                ['exception' => $exception]
            );
        $this->logger->expects($this->never())
            ->method('warning');
        $this->notificationAlertManager->expects(self::once())
            ->method('addNotificationAlert')
            ->willReturnCallback(function (NotificationAlertInterface $notificationAlert) {
                self::assertEquals(
                    'Attempt to load email body failed. Error: Cannot find a body for "test email" email.',
                    $notificationAlert->toArray()['message']
                );

                return 'test_id';
            });
        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertsByAlertTypeForUserAndOrganization');
        $this->notificationAlertManager->expects(self::never())
            ->method('resolveNotificationAlertsByAlertTypeAndStepForUserAndOrganization');

        $this->synchronizer->syncOneEmailBody($email);

        $this->assertSame($emailBody, $email->getEmailBody());
    }

    public function testSyncOneEmailBodyWithSyncWithNotificationAlertException()
    {
        $this->expectException(LoadEmailBodyFailedException::class);
        $this->expectExceptionMessage('Cannot load a body for "test2 email" email.');

        $email = new TestEmailEntity(456);
        $email->setSubject('test2 email');
        $emailBody = new EmailBody();
        $emailUser = new EmailUser();

        $user = new User();
        $user->setId(85);
        $organization = new Organization();
        $organization->setId(789);
        $origin = new TestEmailOrigin(489);
        $origin->setActive(true);
        $origin->setOwner($user);
        $origin->setOrganization($organization);
        $folder = new EmailFolder();
        $folder->setOrigin($origin);
        $origin->addFolder($folder);
        $emailUser->setOrigin($origin);
        $emailUser->addFolder($folder);
        $email->addEmailUser($emailUser);

        $alert = EmailSyncNotificationAlert::createForAuthFail('Cannot connect to the IMAP server');
        $innerException = new \Exception('Fail to connect');
        $exception = new SyncWithNotificationAlertException($alert, 'Fail to connect', 500, $innerException);

        $loader = $this->createMock(EmailBodyLoaderInterface::class);

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->willReturn($loader);
        $loader->expects($this->once())
            ->method('loadEmailBody')
            ->willThrowException($exception);

        $this->em->expects($this->once())
            ->method('persist');
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Load email body failed. Email id: 456. Error: Fail to connect',
                ['exception' => $innerException]
            );
        $this->logger->expects($this->never())
            ->method('warning');
        $this->notificationAlertManager->expects(self::once())
            ->method('addNotificationAlert')
            ->willReturnCallback(function (NotificationAlertInterface $notificationAlert) {
                self::assertEquals(
                    'Cannot connect to the IMAP server',
                    $notificationAlert->toArray()['message']
                );
                return 'testId';
            });
        $this->notificationAlertManager->expects(self::never())
            ->method('resolveNotificationAlertsByAlertTypeForUserAndOrganization');
        $this->notificationAlertManager->expects(self::never())
            ->method('resolveNotificationAlertsByAlertTypeAndStepForUserAndOrganization');

        $this->synchronizer->syncOneEmailBody($email);

        $this->assertSame($emailBody, $email->getEmailBody());
    }

    public function testSyncOnEmptyData()
    {
        $repo = $this->createMock(EmailRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('getEmailsWithoutBody')
            ->willReturn([]);
        $this->logger->expects($this->once())
            ->method('info')
            ->with('All emails was processed');
        $this->notificationAlertManager->expects(self::never())
            ->method('addNotificationAlert');
        $this->notificationAlertManager->expects(self::never())
            ->method('resolveNotificationAlertsByAlertTypeForUserAndOrganization');
        $this->notificationAlertManager->expects(self::never())
            ->method('resolveNotificationAlertsByAlertTypeAndStepForUserAndOrganization');

        $this->synchronizer->sync();
    }

    public function testSync()
    {
        $email = new TestEmailEntity(489);
        $email->setSubject('Test email');
        $emailBody = new EmailBody();
        $emailUser = new EmailUser();

        $user = new User();
        $user->setId(41);
        $organization = new Organization();
        $organization->setId(44);
        $origin = new TestEmailOrigin();
        $origin->setActive(true);
        $origin->setOwner($user);
        $origin->setOrganization($organization);
        $folder = new EmailFolder();
        $folder->setOrigin($origin);
        $origin->addFolder($folder);
        $emailUser->setOrigin($origin);
        $emailUser->addFolder($folder);
        $email->addEmailUser($emailUser);

        $repo = $this->createMock(EmailRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);
        $runCount = 0;
        $repo->expects($this->exactly(2))
            ->method('getEmailsWithoutBody')
            ->willReturnCallback(function () use (&$runCount, $email) {
                $runCount++;
                return $runCount === 1 ? [$email] : [];
            });

        $loader = $this->createMock(EmailBodyLoaderInterface::class);

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->willReturn($loader);
        $loader->expects($this->once())
            ->method('loadEmailBody')
            ->with(
                $this->identicalTo($folder),
                $this->identicalTo($email),
                $this->identicalTo($this->em)
            )
            ->willReturn($emailBody);

        $this->em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo($email));
        $this->em->expects($this->once())
            ->method('clear');
        $this->logger->expects($this->once())
            ->method('notice');
        $this->logger->expects($this->exactly(2))
            ->method('info');
        $this->logger->expects($this->never())
            ->method('warning');
        $this->notificationAlertManager->expects(self::never())
            ->method('addNotificationAlert');
        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertsByAlertTypeForUserAndOrganization');
        $this->notificationAlertManager->expects(self::once())
            ->method('resolveNotificationAlertsByAlertTypeAndStepForUserAndOrganization');

        $this->synchronizer->sync();

        $this->assertSame($emailBody, $email->getEmailBody());
    }
}

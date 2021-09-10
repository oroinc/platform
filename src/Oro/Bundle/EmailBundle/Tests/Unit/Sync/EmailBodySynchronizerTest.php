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
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderSelector;
use Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
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

    /** @var EmailBodySynchronizer */
    private $synchronizer;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->selector = $this->createMock(EmailBodyLoaderSelector::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->em = $this->createMock(EntityManager::class);

        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->em);

        $this->synchronizer = new EmailBodySynchronizer(
            $this->selector,
            $this->doctrine,
            $this->dispatcher
        );
        $this->synchronizer->setLogger($this->logger);
    }

    public function testSyncOneEmailBodyForAlreadyCached()
    {
        $email = new Email();
        $email->setEmailBody(new EmailBody());

        $this->selector->expects($this->never())
            ->method('select');

        $this->synchronizer->syncOneEmailBody($email);
    }

    public function testSyncOneEmailBody()
    {
        $email = new Email();
        $emailBody = new EmailBody();
        $emailUser = new EmailUser();

        $origin = new TestEmailOrigin();
        $origin->setActive(true);
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

        $origin = new TestEmailOrigin();
        $origin->setActive(true);
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

        $origin = new TestEmailOrigin();
        $origin->setActive(true);
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
                'Attempt to load email body failed. Email id: 123. Error: Cannot find a body for "test email" email.',
                ['exception' => $exception]
            );
        $this->logger->expects($this->never())
            ->method('warning');

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

        $this->synchronizer->sync();
    }

    public function testSync()
    {
        $email = new Email();
        $email->setSubject('Test email');
        $emailBody = new EmailBody();
        $emailUser = new EmailUser();

        $origin = new TestEmailOrigin();
        $origin->setActive(true);
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

        $this->synchronizer->sync();

        $this->assertSame($emailBody, $email->getEmailBody());
    }
}

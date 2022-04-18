<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;
use Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailEntity;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;

class EmailBodySynchronizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $selector;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $dispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var EmailBodySynchronizer */
    protected $synchronizer;

    protected function setUp(): void
    {
        $this->logger   = $this->createMock('Psr\Log\LoggerInterface');
        $this->selector = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderSelector')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine   = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->em->expects(self::any())
            ->method('isOpen')
            ->willReturn(true);
        $this->doctrine->expects($this->any())->method('getManager')->willReturn($this->em);

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
        $email     = new TestEmailEntity(123);
        $email->setSubject('Test Email');
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

        $loader = $this->createMock('Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface');

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue($loader));
        $loader->expects($this->once())
            ->method('loadEmailBody')
            ->with(
                $this->identicalTo($folder),
                $this->identicalTo($email),
                $this->identicalTo($this->em)
            )
            ->will($this->returnValue($emailBody));

        $this->em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo($email));

        $this->logger->expects(self::once())
            ->method('notice')
            ->with('The "Test Email" (ID: 123) email body was synced.');
        $this->logger->expects($this->never())
            ->method('warning');

        $this->synchronizer->syncOneEmailBody($email);

        $this->assertSame($emailBody, $email->getEmailBody());
    }

    public function testSyncOneEmailBodyFailure()
    {
        $email = new TestEmailEntity(123);
        $email->setSubject('test email');
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

        $loader = $this->createMock('Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface');

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue($loader));
        $loader->expects($this->once())
            ->method('loadEmailBody')
            ->will($this->throwException($exception));

        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->expects(self::once())
            ->method('getTableName')
            ->willReturn('oro_email');
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('update')
            ->with('oro_email', ['body_synced' => true], ['id' => 123]);
        $this->em->expects(self::exactly(3))
            ->method('isOpen')
            ->willReturn(true);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(Email::class)
            ->willReturn($classMetadata);
        $this->em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->logger->expects($this->once())
            ->method('info');

        $this->synchronizer->syncOneEmailBody($email);
    }

    public function testSyncOneEmailBodyNotFound()
    {
        $email = new Email();
        ReflectionUtil::setId($email, 123);
        $email->setSubject('test email');
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

        $loader = $this->createMock('Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface');

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue($loader));
        $loader->expects($this->once())
            ->method('loadEmailBody')
            ->will($this->throwException($exception));

        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->expects(self::once())
            ->method('getTableName')
            ->willReturn('oro_email');
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('update')
            ->with('oro_email', ['body_synced' => true], ['id' => 123]);
        $this->em->expects(self::exactly(3))
            ->method('isOpen')
            ->willReturn(true);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(Email::class)
            ->willReturn($classMetadata);
        $this->em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->logger->expects($this->once())
            ->method('notice')
            ->with(
                'Attempt to load email body from remote server failed. Email id: 123. '
                . 'Error: Cannot find a body for "test email" email.',
                ['exception' => $exception]
            );
        $this->logger->expects($this->never())
            ->method('warning');

        $this->synchronizer->syncOneEmailBody($email);
    }

    public function testSyncOnEmptyData()
    {
        $repo = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())->method('getRepository')->willReturn($repo);
        $repo->expects($this->once())->method('getEmailIdsWithoutBody')
            ->willReturn([]);
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'All emails was processed'
            );

        $this->synchronizer->sync();
    }

    public function testSyncOneEmailBodyWithExceptionDuringSave(): void
    {
        $email = new TestEmailEntity(789);
        $email->setSubject('test email');
        $emailUser = new EmailUser();
        $emailBody = new EmailBody();

        $origin = new TestEmailOrigin();
        $origin->setActive(true);
        $folder = new EmailFolder();
        $folder->setOrigin($origin);
        $origin->addFolder($folder);
        $emailUser->setOrigin($origin);
        $emailUser->addFolder($folder);
        $email->addEmailUser($emailUser);

        $exception = new \Exception('test exception');

        $loader = $this->createMock(EmailBodyLoaderInterface::class);

        $this->selector->expects(self::once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->willReturn($loader);
        $loader->expects(self::once())
            ->method('loadEmailBody')
            ->willReturn($emailBody);

        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->expects(self::once())
            ->method('getTableName')
            ->willReturn('oro_email');
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('update')
            ->with('oro_email', ['body_synced' => true], ['id' => 789]);
        $this->em->expects(self::exactly(3))
            ->method('isOpen')
            ->willReturn(true);
        $this->em->expects(self::once())
            ->method('flush')
            ->with($this->identicalTo($email))
            ->willThrowException($exception);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(Email::class)
            ->willReturn($classMetadata);
        $this->em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->logger->expects(self::once())
            ->method('info')
            ->with(
                'Load email body failed. Email id: 789. Error: test exception',
                ['exception' => $exception]
            );
        $this->logger->expects(self::never())
            ->method('warning');

        $this->synchronizer->syncOneEmailBody($email);
    }

    public function testSync()
    {
        $email     = new TestEmailEntity(489);
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

        $repo = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturn($repo);
        $runCount = 0;
        $repo->expects($this->exactly(2))
            ->method('getEmailIdsWithoutBody')
            ->willReturnCallback(
                function () use (&$runCount, $email) {
                    $runCount++;
                    return $runCount === 1 ? [$email->getId()] : [];
                }
            );

        $loader = $this->createMock('Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface');

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue($loader));
        $loader->expects($this->once())
            ->method('loadEmailBody')
            ->with(
                $this->identicalTo($folder),
                $this->identicalTo($email),
                $this->identicalTo($this->em)
            )
            ->will($this->returnValue($emailBody));

        $this->em->expects(self::once())
            ->method('find')
            ->with(Email::class, 489)
            ->willReturn($email);
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

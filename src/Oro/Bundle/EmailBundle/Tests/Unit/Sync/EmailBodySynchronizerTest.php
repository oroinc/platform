<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\EmailBundle\Entity\EmailUser;

class EmailBodySynchronizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $selector;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var EmailBodySynchronizer */
    protected $synchronizer;

    protected function setUp()
    {
        $this->logger   = $this->getMock('Psr\Log\LoggerInterface');
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
        $email     = new Email();
        $emailBody = new EmailBody();
        $emailUser = new EmailUser();

        $origin = new TestEmailOrigin();
        $folder = new EmailFolder();
        $folder->setOrigin($origin);
        $emailUser->setOrigin($origin);
        $emailUser->addFolder($folder);
        $email->addEmailUser($emailUser);

        $loader = $this->getMock('Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface');

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

        $this->logger->expects($this->never())
            ->method('notice');
        $this->logger->expects($this->never())
            ->method('warning');

        $this->synchronizer->syncOneEmailBody($email);

        $this->assertSame($emailBody, $email->getEmailBody());
    }

    /**
     * @expectedException \Oro\Bundle\EmailBundle\Exception\LoadEmailBodyFailedException
     * @expectedExceptionMessage Cannot load a body for "test email" email. Reason: some exception.
     */
    public function testSyncOneEmailBodyFailure()
    {
        $email = new Email();
        ReflectionUtil::setId($email, 123);
        $email->setSubject('test email');
        $emailBody = new EmailBody();
        $emailUser = new EmailUser();

        $origin = new TestEmailOrigin();
        $folder = new EmailFolder();
        $folder->setOrigin($origin);
        $emailUser->setOrigin($origin);
        $emailUser->addFolder($folder);
        $email->addEmailUser($emailUser);

        $exception = new \Exception('some exception');

        $loader = $this->getMock('Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface');

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue($loader));
        $loader->expects($this->once())
            ->method('loadEmailBody')
            ->will($this->throwException($exception));

        $this->em->expects($this->never())
            ->method('persist');
        $this->em->expects($this->never())
            ->method('flush');

        $this->logger->expects($this->never())
            ->method('notice');
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Load email body failed. Email id: 123. Error: some exception.',
                ['exception' => $exception]
            );

        $this->synchronizer->syncOneEmailBody($email);

        $this->assertSame($emailBody, $email->getEmailBody());
    }

    /**
     * @expectedException \Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException
     * @expectedExceptionMessage Cannot find a body for "test email" email.
     */
    public function testSyncOneEmailBodyNotFound()
    {
        $email = new Email();
        ReflectionUtil::setId($email, 123);
        $email->setSubject('test email');
        $emailBody = new EmailBody();
        $emailUser = new EmailUser();

        $origin = new TestEmailOrigin();
        $folder = new EmailFolder();
        $folder->setOrigin($origin);
        $emailUser->setOrigin($origin);
        $emailUser->addFolder($folder);
        $email->addEmailUser($emailUser);

        $exception = new EmailBodyNotFoundException($email);

        $loader = $this->getMock('Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface');

        $this->selector->expects($this->once())
            ->method('select')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue($loader));
        $loader->expects($this->once())
            ->method('loadEmailBody')
            ->will($this->throwException($exception));

        $this->em->expects($this->never())
            ->method('persist');
        $this->em->expects($this->never())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('notice')
            ->with(
                'Load email body failed. Email id: 123. Error: Cannot find a body for "test email" email.',
                ['exception' => $exception]
            );
        $this->logger->expects($this->never())
            ->method('warning');

        $this->synchronizer->syncOneEmailBody($email);

        $this->assertSame($emailBody, $email->getEmailBody());
    }

    public function testSyncOnEmptyData()
    {
        $repo = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())->method('getRepository')->willReturn($repo);
        $repo->expects($this->once())->method('getEmailsWithoutBody')
            ->willReturn([]);
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'All emails was processed'
            );

        $this->synchronizer->sync();
    }

    public function testSync()
    {
        $email     = new Email();
        $email->setSubject('Test email');
        $emailBody = new EmailBody();
        $emailUser = new EmailUser();

        $origin = new TestEmailOrigin();
        $folder = new EmailFolder();
        $folder->setOrigin($origin);
        $emailUser->setOrigin($origin);
        $emailUser->addFolder($folder);
        $email->addEmailUser($emailUser);

        $repo = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())->method('getRepository')->willReturn($repo);
        $runCount = 0;
        $repo->expects($this->exactly(2))
            ->method('getEmailsWithoutBody')
            ->willReturnCallback(
                function () use (&$runCount, $email) {
                    $runCount++;
                    return $runCount === 1 ? [$email] : [];
                }
            );

        $loader = $this->getMock('Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface');

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

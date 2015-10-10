<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Cache;

use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\EmailBundle\Entity\EmailUser;

class EmailCacheManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $selector;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    /** @var EmailCacheManager */
    protected $manager;

    protected function setUp()
    {
        $this->logger   = $this->getMock('Psr\Log\LoggerInterface');
        $this->selector = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderSelector')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em       = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new EmailCacheManager(
            $this->selector,
            $this->em,
            $this->dispatcher
        );
        $this->manager->setLogger($this->logger);
    }

    public function testEnsureEmailBodyCachedForAlreadyCached()
    {
        $email = new Email();
        $email->setEmailBody(new EmailBody());

        $this->selector->expects($this->never())
            ->method('select');

        $this->manager->ensureEmailBodyCached($email);
    }

    public function testEnsureEmailBodyCached()
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
            ->method('persist')
            ->with($this->identicalTo($email));
        $this->em->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->never())
            ->method('notice');
        $this->logger->expects($this->never())
            ->method('warning');

        $this->manager->ensureEmailBodyCached($email);

        $this->assertSame($emailBody, $email->getEmailBody());
    }

    /**
     * @expectedException \Oro\Bundle\EmailBundle\Exception\LoadEmailBodyFailedException
     * @expectedExceptionMessage Cannot load a body for "test email" email. Reason: some exception.
     */
    public function testEnsureEmailBodyCachedFailure()
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

        $this->manager->ensureEmailBodyCached($email);

        $this->assertSame($emailBody, $email->getEmailBody());
    }

    /**
     * @expectedException \Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException
     * @expectedExceptionMessage Cannot find a body for "test email" email.
     */
    public function testEnsureEmailBodyCachedNotFound()
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

        $this->manager->ensureEmailBodyCached($email);

        $this->assertSame($emailBody, $email->getEmailBody());
    }
}

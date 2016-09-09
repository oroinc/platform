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
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailBodySynchronizer;

    /** @var EmailCacheManager */
    protected $manager;

    protected function setUp()
    {
        $this->em       = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailBodySynchronizer = $this->getMockBuilder('Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new EmailCacheManager(
            $this->em,
            $this->dispatcher,
            $this->emailBodySynchronizer
        );
    }

    public function testEnsureEmailBodyCachedForAlreadyCached()
    {
        $email = new Email();
        $email->setEmailBody(new EmailBody());

        $this->emailBodySynchronizer->expects($this->never())
            ->method('syncOneEmailBody');

        $this->manager->ensureEmailBodyCached($email);
    }

    public function testEnsureEmailBodyCached()
    {
        $email = new Email();

        $this->emailBodySynchronizer
            ->expects($this->once())
            ->method('syncOneEmailBody');

        $this->manager->ensureEmailBodyCached($email);
    }
}

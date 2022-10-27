<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Cache;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailCacheManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $emailBodySynchronizer;

    /** @var EmailCacheManager */
    private $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->emailBodySynchronizer = $this->createMock(EmailBodySynchronizer::class);

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

        $this->emailBodySynchronizer->expects($this->once())
            ->method('syncOneEmailBody');

        $this->manager->ensureEmailBodyCached($email);
    }
}

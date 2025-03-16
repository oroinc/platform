<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Cache;

use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Event\EmailBodyLoaded;
use Oro\Bundle\EmailBundle\Sync\EmailBodySynchronizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailCacheManagerTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private EmailBodySynchronizer&MockObject $emailBodySynchronizer;
    private EmailCacheManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->emailBodySynchronizer = $this->createMock(EmailBodySynchronizer::class);

        $this->manager = new EmailCacheManager(
            $this->eventDispatcher,
            $this->emailBodySynchronizer
        );
    }

    public function testEnsureEmailBodyCachedForAlreadyCached(): void
    {
        $email = new Email();
        $email->setEmailBody(new EmailBody());

        $this->emailBodySynchronizer->expects(self::never())
            ->method('syncOneEmailBody');
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EmailBodyLoaded::class), EmailBodyLoaded::NAME);

        $this->manager->ensureEmailBodyCached($email);
    }

    public function testEnsureEmailBodyCached(): void
    {
        $email = new Email();

        $this->emailBodySynchronizer->expects(self::once())
            ->method('syncOneEmailBody')
            ->with(self::identicalTo($email), true);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EmailBodyLoaded::class), EmailBodyLoaded::NAME);

        $this->manager->ensureEmailBodyCached($email);
    }
}

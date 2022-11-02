<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Event\PrepareContextTitleEvent;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\EventListener\PrepareContextTitleListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PrepareContextTitleListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PrepareContextTitleListener */
    private $listener;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new PrepareContextTitleListener($this->urlGenerator, $this->doctrine);
    }

    public function testPrepareContextTitleForNotEmailEntity()
    {
        $item = ['title' => 'title'];

        $event = new PrepareContextTitleEvent($item, 'test');
        $this->listener->prepareContextTitle($event);

        $this->assertEquals($item, $event->getItem());
    }

    public function testPrepareContextTitle()
    {
        $emailId = 123;
        $emailTitle = 'test email title';
        $emailUrl = 'test-email-url';

        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('getSubject')
            ->willReturn($emailTitle);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Email::class, $emailId)
            ->willReturn($email);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Email::class)
            ->willReturn($em);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_email_thread_view', ['id' => $emailId], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($emailUrl);

        $event = new PrepareContextTitleEvent(['title' => 'title', 'targetId' => $emailId], Email::class);
        $this->listener->prepareContextTitle($event);

        $this->assertEquals(
            ['title' => $emailTitle, 'link' => $emailUrl, 'targetId' => $emailId],
            $event->getItem()
        );
    }
}

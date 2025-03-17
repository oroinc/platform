<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponsesTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\EventListener\AutoResponseListener;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AutoResponseListenerTest extends TestCase
{
    private AutoResponseManager&MockObject $autoResponseManager;
    private MessageProducerInterface&MockObject $producer;
    private AutoResponseListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->autoResponseManager = $this->createMock(AutoResponseManager::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);

        $container = TestContainerBuilder::create()
            ->add(AutoResponseManager::class, $this->autoResponseManager)
            ->add(MessageProducerInterface::class, $this->producer)
            ->getContainer($this);

        $this->listener = new AutoResponseListener($container);
    }

    public function testShouldPublishEmailIdsIfTheyHasAutoResponse(): void
    {
        $this->autoResponseManager->expects(self::exactly(2))
            ->method('hasAutoResponses')
            ->willReturn(true);

        $this->producer->expects(self::once())
            ->method('send')
            ->with(SendAutoResponsesTopic::getName(), ['ids' => [123, 12345]]);

        $email1 = new Email();
        ReflectionUtil::setId($email1, 123);

        $emailBody1 = new EmailBody();
        $emailBody1->setEmail($email1);

        $email2 = new Email();
        ReflectionUtil::setId($email2, 12345);

        $emailBody2 = new EmailBody();
        $emailBody2->setEmail($email2);

        ReflectionUtil::setPropertyValue($this->listener, 'emailBodies', [$emailBody1, $emailBody2]);

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testShouldNotPublishEmailIdsIfThereIsNotEmailBodies(): void
    {
        $this->autoResponseManager->expects(self::never())
            ->method('hasAutoResponses');

        $this->producer->expects(self::never())
            ->method('send');

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testShouldFilterOutEmailsWhichHasNoAutoResponse(): void
    {
        $email1 = new Email();
        ReflectionUtil::setId($email1, 123);

        $emailBody1 = new EmailBody();
        $emailBody1->setEmail($email1);

        $email2 = new Email();
        ReflectionUtil::setId($email2, 12345);

        $emailBody2 = new EmailBody();
        $emailBody2->setEmail($email2);

        ReflectionUtil::setPropertyValue($this->listener, 'emailBodies', [$emailBody1, $emailBody2]);

        $this->autoResponseManager->expects(self::exactly(2))
            ->method('hasAutoResponses')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->producer->expects(self::once())
            ->method('send')
            ->with(SendAutoResponsesTopic::getName(), ['ids' => [12345]]);

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testShouldNotPublishEmailIdsIfEmailFeatureIsTurnedOff(): void
    {
        $this->autoResponseManager->expects(self::never())
            ->method('hasAutoResponses');

        $this->producer->expects(self::never())
            ->method('send')
            ->with($this->anything());

        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with($this->anything())
            ->willReturn(false);
        $this->listener->setFeatureChecker($featureChecker);
        $this->listener->addFeature('email');

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }
}

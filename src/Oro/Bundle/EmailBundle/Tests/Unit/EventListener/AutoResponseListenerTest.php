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

class AutoResponseListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AutoResponseManager|\PHPUnit\Framework\MockObject\MockObject */
    private $autoResponseManager;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var AutoResponseListener */
    private $listener;

    protected function setUp(): void
    {
        $this->autoResponseManager = $this->createMock(AutoResponseManager::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);

        $container = TestContainerBuilder::create()
            ->add('oro_email.autoresponserule_manager', $this->autoResponseManager)
            ->add(MessageProducerInterface::class, $this->producer)
            ->getContainer($this);

        $this->listener = new AutoResponseListener($container);
    }

    public function testShouldPublishEmailIdsIfTheyHasAutoResponse()
    {
        $this->autoResponseManager->expects($this->exactly(2))
            ->method('hasAutoResponses')
            ->willReturn(true);

        $this->producer->expects($this->once())
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

    public function testShouldNotPublishEmailIdsIfThereIsNotEmailBodies()
    {
        $this->autoResponseManager->expects($this->never())
            ->method('hasAutoResponses');

        $this->producer->expects($this->never())
            ->method('send');

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testShouldFilterOutEmailsWhichHasNoAutoResponse()
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

        $this->autoResponseManager->expects($this->exactly(2))
            ->method('hasAutoResponses')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->producer->expects($this->once())
            ->method('send')
            ->with(SendAutoResponsesTopic::getName(), ['ids' => [12345]]);

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testShouldNotPublishEmailIdsIfEmailFeatureIsTurnedOff()
    {
        $this->autoResponseManager->expects($this->never())
            ->method('hasAutoResponses');

        $this->producer->expects($this->never())
            ->method('send')
            ->with($this->anything());

        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($this->anything())
            ->willReturn(false);
        $this->listener->setFeatureChecker($featureChecker);
        $this->listener->addFeature('email');

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }
}

<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\EventListener\AutoResponseListener;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class AutoResponseListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $autoResponseManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $producer;

    /** @var AutoResponseListener */
    protected $listener;

    protected function setUp()
    {
        $this->autoResponseManager = $this->getMockBuilder(AutoResponseManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->producer = $this->createMock(MessageProducerInterface::class);

        $container = TestContainerBuilder::create()
            ->add('oro_email.autoresponserule_manager', $this->autoResponseManager)
            ->add('oro_message_queue.client.message_producer', $this->producer)
            ->getContainer($this);

        $this->listener = new AutoResponseListener($container);
    }

    public function testShouldPublishEmailIdsIfTheyHasAutoResponse()
    {
        $this->autoResponseManager->expects($this->exactly(2))
            ->method('hasAutoResponses')
            ->will($this->returnValue(true));

        $this->producer->expects($this->once())
            ->method('send')
            ->with(Topics::SEND_AUTO_RESPONSES, ['ids' => [123, 12345]]);

        $email1 = new Email();
        $this->writePropertyValue($email1, 'id', 123);

        $emailBody1 = new EmailBody();
        $emailBody1->setEmail($email1);

        $email2 = new Email();
        $this->writePropertyValue($email2, 'id', 12345);

        $emailBody2 = new EmailBody();
        $emailBody2->setEmail($email2);

        $this->writePropertyValue($this->listener, 'emailBodies', [$emailBody1, $emailBody2]);

        $this->listener->postFlush($this->createPostFlushEventArgsMock());
    }

    public function testShouldNotPublishEmailIdsIfThereIsNotEmailBodies()
    {
        $this->autoResponseManager->expects($this->never())
            ->method('hasAutoResponses');

        $this->producer->expects($this->never())
            ->method('send');

        $this->listener->postFlush($this->createPostFlushEventArgsMock());
    }

    public function testShouldFilterOutEmailsWhichHasNoAutoResponse()
    {
        $this->autoResponseManager->expects($this->at(0))
            ->method('hasAutoResponses')
            ->will($this->returnValue(false));
        $this->autoResponseManager->expects($this->at(1))
            ->method('hasAutoResponses')
            ->will($this->returnValue(true));

        $this->producer->expects($this->once())
            ->method('send')
            ->with(Topics::SEND_AUTO_RESPONSES, ['ids' => [12345]]);

        $email1 = new Email();
        $this->writePropertyValue($email1, 'id', 123);

        $emailBody1 = new EmailBody();
        $emailBody1->setEmail($email1);

        $email2 = new Email();
        $this->writePropertyValue($email2, 'id', 12345);

        $emailBody2 = new EmailBody();
        $emailBody2->setEmail($email2);

        $this->writePropertyValue($this->listener, 'emailBodies', [$emailBody1, $emailBody2]);

        $this->listener->postFlush($this->createPostFlushEventArgsMock());
    }

    public function testShouldNotPublishEmailIdsIfEmailFeatureIsTurnedOff()
    {
        $this->autoResponseManager->expects($this->never())
            ->method('hasAutoResponses');

        $this->producer->expects($this->never())
            ->method('send')
            ->with($this->anything());

        $featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->setMethods(['isFeatureEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($this->anything())
            ->willReturn(false);
        $this->listener->setFeatureChecker($featureChecker);
        $this->listener->addFeature('email');

        $this->listener->postFlush($this->createPostFlushEventArgsMock());
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed  $value
     */
    private function writePropertyValue($object, $property, $value)
    {
        $refProperty = new \ReflectionProperty(get_class($object), $property);
        $refProperty->setAccessible(true);
        $refProperty->setValue($object, $value);
        $refProperty->setAccessible(false);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|PostFlushEventArgs
     */
    private function createPostFlushEventArgsMock()
    {
        return $this->createMock(PostFlushEventArgs::class);
    }
}

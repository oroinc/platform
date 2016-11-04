<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\EventListener\AutoResponseListener;

use Symfony\Component\DependencyInjection\Container;

class AutoResponseListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new AutoResponseListener(new ServiceLink(new Container(), 'service'), $this->createMessageProducerMock());
    }

    public function testShouldPublishEmailIdsIfTheyHasAutoResponse()
    {
        $autoResponseManager = $this->createAutoResponseManagerMock();
        $autoResponseManager
            ->expects($this->exactly(2))
            ->method('hasAutoResponses')
            ->will($this->returnValue(true))
        ;

        $container = new Container();
        $container->set('service', $autoResponseManager);
        $serviceLink = new ServiceLink($container, 'service');

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::SEND_AUTO_RESPONSES, ['ids' => [123, 12345]])
        ;

        $email1 = new Email();
        $this->writePropertyValue($email1, 'id', 123);

        $emailBody1 = new EmailBody();
        $emailBody1->setEmail($email1);

        $email2 = new Email();
        $this->writePropertyValue($email2, 'id', 12345);

        $emailBody2 = new EmailBody();
        $emailBody2->setEmail($email2);

        $listener = new AutoResponseListener($serviceLink, $producer);
        $this->writePropertyValue($listener, 'emailBodies', [$emailBody1, $emailBody2]);

        $listener->postFlush($this->createPostFlushEventArgsMock());
    }

    public function testShouldNotPublishEmailIdsIfThereIsNotEmailBodies()
    {
        $autoResponseManager = $this->createAutoResponseManagerMock();
        $autoResponseManager
            ->expects($this->never())
            ->method('hasAutoResponses')
        ;

        $container = new Container();
        $container->set('service', $autoResponseManager);
        $serviceLink = new ServiceLink($container, 'service');

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('send')
        ;

        $listener = new AutoResponseListener($serviceLink, $producer);
        $listener->postFlush($this->createPostFlushEventArgsMock());
    }

    public function testShouldFilterOutEmailsWhichHasNoAutoResponse()
    {
        $autoResponseManager = $this->createAutoResponseManagerMock();
        $autoResponseManager
            ->expects($this->at(0))
            ->method('hasAutoResponses')
            ->will($this->returnValue(false))
        ;
        $autoResponseManager
            ->expects($this->at(1))
            ->method('hasAutoResponses')
            ->will($this->returnValue(true))
        ;

        $container = new Container();
        $container->set('service', $autoResponseManager);
        $serviceLink = new ServiceLink($container, 'service');

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::SEND_AUTO_RESPONSES, ['ids' => [12345]])
        ;

        $email1 = new Email();
        $this->writePropertyValue($email1, 'id', 123);

        $emailBody1 = new EmailBody();
        $emailBody1->setEmail($email1);

        $email2 = new Email();
        $this->writePropertyValue($email2, 'id', 12345);

        $emailBody2 = new EmailBody();
        $emailBody2->setEmail($email2);

        $listener = new AutoResponseListener($serviceLink, $producer);
        $this->writePropertyValue($listener, 'emailBodies', [$emailBody1, $emailBody2]);

        $listener->postFlush($this->createPostFlushEventArgsMock());
    }

    public function testShouldNotPublishEmailIdsIfEmailFeatureIsTurnedOff()
    {
        $autoResponseManager = $this->createAutoResponseManagerMock();
        $autoResponseManager
            ->expects($this->never())
            ->method('hasAutoResponses');

        $container = new Container();
        $container->set('service', $autoResponseManager);
        $serviceLink = new ServiceLink($container, 'service');

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('send')
            ->with($this->anything());

        $listener = new AutoResponseListener($serviceLink, $producer);
        $featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->setMethods(['isFeatureEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($this->anything())
            ->willReturn(false);
        $listener->setFeatureChecker($featureChecker);
        $listener->addFeature('email');

        $listener->postFlush($this->createPostFlushEventArgsMock());
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
     * @return \PHPUnit_Framework_MockObject_MockObject|AutoResponseManager
     */
    private function createAutoResponseManagerMock()
    {
        return $this->getMock(AutoResponseManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PostFlushEventArgs
     */
    private function createPostFlushEventArgsMock()
    {
        return $this->getMock(PostFlushEventArgs::class, [], [], '', false);
    }
}

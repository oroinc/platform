<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationSender;
use Oro\Bundle\NotificationBundle\Model\EmailNotificationInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Log\LoggerInterface;

class EmailNotificationManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldCreateWithAllRequiredArguments()
    {
        new EmailNotificationManager(
            $this->createEmailRendererMock(),
            $this->createEmailNotificationSenderMock(),
            $this->createLoggerMock()
        );
    }


    public function testShouldSendMessageIfTemplateRendered()
    {
        $testSubject = 'test subject';
        $testBody = 'test body';

        $template = $this->createTemplateMock();
        $template->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('html'))
        ;

        $object = $this->createUserMock();

        $emailRenderer = $this->createEmailRendererMock();
        $emailRenderer->expects($this->once())
            ->method('compileMessage')
            ->with($template, ['entity' => $object])
            ->will($this->returnValue([$testSubject, $testBody]))
        ;

        $notification = $this->createEmailNotificationMock();
        $notification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template))
        ;

        $sender = $this->createEmailNotificationSenderMock();
        $sender
            ->expects($this->once())
            ->method('send')
            ->with($notification, $testSubject, $testBody, 'text/html');

        $manager = new EmailNotificationManager(
            $emailRenderer,
            $sender,
            $this->createLoggerMock()
        );

        $manager->process($object, [$notification]);
    }

    public function testShouldNotSendMessageIfTwigExceptionAppear()
    {
        $configManager = $this->createConfigManagerMock();
        $configManager
            ->expects($this->never())
            ->method('get')
        ;

        $template = $this->createTemplateMock();
        $template->expects($this->never())
            ->method('getType')
        ;

        $object = $this->createUserMock();

        $emailRenderer = $this->createEmailRendererMock();
        $emailRenderer->expects($this->once())
            ->method('compileMessage')
            ->will($this->throwException(new \Twig_Error('An error occured')))
        ;

        $notification = $this->createEmailNotificationMock();
        $notification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template))
        ;

        $sender = $this->createEmailNotificationSenderMock();
        $sender
            ->expects($this->never())
            ->method('send')
        ;

        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('error')
        ;

        $manager = new EmailNotificationManager(
            $emailRenderer,
            $sender,
            $logger
        );

        $manager->process($object, [$notification]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | EmailRenderer
     */
    private function createEmailRendererMock()
    {
        return $this
            ->getMockBuilder(EmailRenderer::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this
            ->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | EmailNotificationSender
     */
    private function createEmailNotificationSenderMock()
    {
        return $this
            ->getMockBuilder(EmailNotificationSender::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | EmailTemplate
     */
    private function createTemplateMock()
    {
        return $this->getMock(EmailTemplate::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | EmailNotificationInterface
     */
    private function createEmailNotificationMock()
    {
        return $this->getMock(EmailNotificationInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | User
     */
    private function createUserMock()
    {
        return $this->getMock(User::class);
    }
}

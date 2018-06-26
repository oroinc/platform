<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Mailer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;
use Oro\Bundle\UserBundle\Mailer\BaseProcessor;

abstract class AbstractProcessorTest extends \PHPUnit\Framework\TestCase
{
    const FROM_EMAIL = 'email_from@example.com';
    const FROM_NAME = 'From Name';

    /** @var EmailTemplateRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $objectRepository;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $managerRegistry;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $objectManager;

    /** @var EmailRenderer|\PHPUnit\Framework\MockObject\MockObject */
    protected $renderer;

    /** @var EmailHolderHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $emailHolderHelper;

    /** @var \Swift_Mailer|\PHPUnit\Framework\MockObject\MockObject */
    protected $mailer;

    /** @var BaseProcessor|\PHPUnit\Framework\MockObject\MockObject */
    protected $mailProcessor;

    /** @var EmailTemplateInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $emailTemplate;

    protected function setUp()
    {
        $this->objectRepository = $this->getMockForClass(
            'Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository'
        );

        $this->objectManager = $this->getMockForClass('Doctrine\Common\Persistence\ObjectManager');
        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailTemplate')
            ->willReturn($this->objectRepository);

        $this->managerRegistry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroEmailBundle:EmailTemplate')
            ->willReturn($this->objectManager);

        $this->setConfigManager();

        $this->renderer = $this->getMockForClass('Oro\Bundle\EmailBundle\Provider\EmailRenderer');

        $this->emailHolderHelper = $this->getMockForClass('Oro\Bundle\EmailBundle\Tools\EmailHolderHelper');

        $this->mailer = $this->getMockForClass('\Swift_Mailer');

        $this->mailProcessor = $this->setProcessor();

        $this->emailTemplate = $this->createMock('Oro\Bundle\EmailBundle\Model\EmailTemplateInterface');
    }

    protected function setConfigManager()
    {
        $this->configManager = $this->getMockForClass('Oro\Bundle\ConfigBundle\Config\ConfigManager');
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_notification.email_notification_sender_email', false, false, null, self::FROM_EMAIL],
                    ['oro_notification.email_notification_sender_name', false, false, null, self::FROM_NAME]
                ]
            );
    }
    
    /**
     * @return BaseProcessor
     */
    protected function setProcessor()
    {
        return new BaseProcessor(
            $this->managerRegistry,
            $this->configManager,
            $this->renderer,
            $this->emailHolderHelper,
            $this->mailer
        );
    }

    protected function tearDown()
    {
        unset(
            $this->objectRepository,
            $this->objectManager,
            $this->managerRegistry,
            $this->configManager,
            $this->renderer,
            $this->emailHolderHelper,
            $this->mailer,
            $this->mailProcessor,
            $this->emailTemplate
        );
    }

    /**
     * @param string $className
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockForClass($className)
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }

    /**
     * @param string $emailTo
     * @param string $subject
     * @param string $body
     * @param string $type
     * @return \Swift_Message
     */
    protected function buildMessage($emailTo, $subject = 'subject', $body = 'body', $type = 'text/plain')
    {
        $message = \Swift_Message::newInstance();
        $message
            ->setSubject($subject)
            ->setFrom(self::FROM_EMAIL, self::FROM_NAME)
            ->setTo($emailTo)
            ->setBody($body, $type);

        return $message;
    }

    /**
     * @param string         $templateName
     * @param array          $templateParams
     * @param \Swift_Message $expectedMessage
     * @param string         $emailType
     */
    protected function assertSendCalled(
        $templateName,
        array $templateParams,
        \Swift_Message $expectedMessage,
        $emailType = 'txt'
    ) {
        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn($emailType);

        $this->objectRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $templateName])
            ->willReturn($this->emailTemplate);

        $this->renderer->expects($this->once())
            ->method('compileMessage')
            ->with($this->emailTemplate, $templateParams)
            ->willReturn([$expectedMessage->getSubject(), $expectedMessage->getBody()]);

        $to = $expectedMessage->getTo();
        $toKeys = array_keys($to);
        $this->emailHolderHelper->expects($this->once())
            ->method('getEmail')
            ->with($this->isInstanceOf('Oro\Bundle\UserBundle\Entity\UserInterface'))
            ->willReturn(array_shift($toKeys));

        $this->mailer->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(
                    function (\Swift_Message $actualMessage) use ($expectedMessage) {
                        $this->assertEquals($expectedMessage->getSubject(), $actualMessage->getSubject());
                        $this->assertEquals($expectedMessage->getFrom(), $actualMessage->getFrom());
                        $this->assertEquals($expectedMessage->getTo(), $actualMessage->getTo());
                        $this->assertEquals($expectedMessage->getBody(), $actualMessage->getBody());
                        $this->assertEquals($expectedMessage->getContentType(), $actualMessage->getContentType());

                        return true;
                    }
                )
            );
    }
}

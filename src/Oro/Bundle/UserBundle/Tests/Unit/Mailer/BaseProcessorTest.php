<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Mailer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;
use Oro\Bundle\UserBundle\Mailer\BaseProcessor;
use Oro\Bundle\UserBundle\Entity\User;

class BaseProcessorTest extends \PHPUnit_Framework_TestCase
{
    const FROM_EMAIL = 'email_from@example.com';
    const FROM_NAME = 'Email From Name';

    /**
     * @var EmailTemplateRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectRepository;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var EmailRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $renderer;

    /**
     * @var EmailHolderHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailHolderHelper;

    /**
     * @var \Swift_Mailer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailer;

    /**
     * @var BaseProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailProcessor;

    /**
     * @var EmailTemplateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
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

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroEmailBundle:EmailTemplate')
            ->willReturn($this->objectManager);

        $this->configManager = $this->getMockForClass('Oro\Bundle\ConfigBundle\Config\ConfigManager');
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function ($param) {
                    switch ($param) {
                        case 'oro_notification.email_notification_sender_email':
                            return self::FROM_EMAIL;
                            break;
                        case 'oro_notification.email_notification_sender_name':
                            return self::FROM_NAME;
                            break;
                        default:
                            return null;
                            break;
                    }
                }
            );

        $this->renderer = $this->getMockForClass('Oro\Bundle\EmailBundle\Provider\EmailRenderer');

        $this->emailHolderHelper = $this->getMockForClass('Oro\Bundle\EmailBundle\Tools\EmailHolderHelper');

        $this->mailer = $this->getMockForClass('\Swift_Mailer');

        $this->mailProcessor = new BaseProcessor(
            $this->managerRegistry,
            $this->configManager,
            $this->renderer,
            $this->emailHolderHelper,
            $this->mailer
        );

        $this->emailTemplate = $this->getMock('Oro\Bundle\EmailBundle\Model\EmailTemplateInterface');
    }

    protected function tearDown()
    {
        unset($this->user, $this->objectRepository, $this->objectManager, $this->managerRegistry, $this->configManager);
        unset($this->renderer, $this->emailHolderHelper, $this->mailer, $this->mailProcessor, $this->emailTemplate);
    }

    /**
     *
     * @dataProvider sendEmailResultProvider
     *
     * @param User   $user
     * @param string $templateName
     * @param array  $templateParams
     * @param array  $templateData
     * @param string $emailType
     * @param string $expectedMessage
     * @param string $mailerResult
     */
    public function testSendEmail(
        User $user,
        $templateName,
        array $templateParams,
        array $templateData,
        $emailType,
        $expectedMessage,
        $mailerResult
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
            ->willReturn($templateData);

        $this->emailHolderHelper->expects($this->once())
            ->method('getEmail')
            ->with($user)
            ->willReturn($user->getEmail());

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf($expectedMessage))
            ->willReturn($mailerResult);

        $this->assertEquals(
            $mailerResult,
            $this->mailProcessor->getEmailTemplateAndSendEmail($user, $templateName, $templateParams)
        );
    }

    /**
     * @return array
     */
    public function sendEmailResultProvider()
    {
        $user = new User();
        $user->setEmail('email_to@example.com');

        $subject = 'rendered subject';
        $email = 'rendered template';

        $typePlain = 'text/plain';
        $typeHtml = 'text/html';

        $messagePlain = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom(self::FROM_EMAIL, self::FROM_NAME)
            ->setTo($user->getEmail())
            ->setBody($email, $typePlain);

        $messageHtml = clone $messagePlain;
        $messageHtml->setBody($email, $typeHtml);

        return [
            [
                'user' => $user,
                'templateName' => 'test_email',
                'templateParams' => ['entity' => $user],
                'templateData' => [$subject, $email],
                'emailType' => 'txt',
                'expectedMessage' => $messagePlain,
                'mailerResult' => 1
            ],
            [
                'user' => $user,
                'templateName' => 'test_email',
                'templateParams' => ['entity' => $user],
                'templateData' => [$subject, $email],
                'emailType' => 'html',
                'expectedMessage' => $messageHtml,
                'mailerResult' => 0
            ]
        ];
    }

    /**
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockForClass($className)
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }
}

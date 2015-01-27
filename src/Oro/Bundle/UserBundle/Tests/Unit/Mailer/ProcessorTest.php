<?php

namespace Oro\Bundle\UserBundle\Tests\Mailer;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Entity\User;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailTemplateRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectRepository;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var EmailRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $renderer;

    /**
     * @var UserManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userManager;

    /**
     * @var \Swift_Mailer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailer;

    /**
     * @var Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailProcessor;

    /**
     * @var EmailTemplateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailTemplate;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $templateData;

    protected function setUp()
    {
        $this->user = new User();
        $this->templateData = ['templateData'];

        $this->objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectRepository = $this->getMockBuilder(
            'Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->objectRepository);

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->renderer = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRenderer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->renderer->expects($this->any())
            ->method('compileMessage')
            ->willReturn($this->templateData);

        $this->userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\UserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mailer = $this->getMockBuilder('\Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mailProcessor = $this->getMockBuilder('Oro\Bundle\UserBundle\Mailer\Processor')
            ->setConstructorArgs(
                [
                    $this->objectManager,
                    $this->configManager,
                    $this->renderer,
                    $this->userManager,
                    $this->mailer,
                ]
            )
            ->setMethods(['sendEmail'])
            ->getMock();

        $this->emailTemplate = $this->getMock('Oro\Bundle\EmailBundle\Model\EmailTemplateInterface');
    }

    /**
     * @param string  $methodName
     * @param string  $templateName
     * @param string  $getTypeResult
     * @param string  $typeValue
     * @param boolean $sendEmailResult
     *
     * @dataProvider sendEmailResultProvider
     */
    public function testSendEmail($methodName, $templateName, $getTypeResult, $typeValue, $sendEmailResult)
    {
        $this->objectRepository->expects($this->once())
            ->method('findByName')
            ->with($templateName)
            ->willReturn($this->emailTemplate);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn($getTypeResult);

        $this->mailProcessor->expects($this->once())
            ->method('sendEmail')
            ->with($this->user, $this->templateData, $typeValue)
            ->willReturn($sendEmailResult);

        $this->assertEquals($sendEmailResult, $this->mailProcessor->{$methodName}($this->user));
    }

    public function sendEmailResultProvider()
    {
        return [
            [
                'sendChangePasswordEmail',
                Processor::TEMPLATE_USER_CHANGE_PASSWORD,
                'txt',
                'text/plain',
                true,
            ],
            [
                'sendChangePasswordEmail',
                Processor::TEMPLATE_USER_CHANGE_PASSWORD,
                'html',
                'text/html',
                false,
            ],
            [
                'sendResetPasswordEmail',
                Processor::TEMPLATE_USER_RESET_PASSWORD,
                null,
                'text/html',
                true,
            ],
            [
                'sendResetPasswordEmail',
                Processor::TEMPLATE_USER_RESET_PASSWORD,
                'txt',
                'text/plain',
                false,
            ],
            [
                'sendResetPasswordAsAdminEmail',
                Processor::TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN,
                'txt',
                'text/plain',
                true,
            ],
            [
                'sendResetPasswordAsAdminEmail',
                Processor::TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN,
                1,
                'text/html',
                false,
            ],
        ];
    }
}

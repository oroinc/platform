<?php

namespace Oro\Bundle\UserBundle\Tests\Mailer;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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

    protected function setUp()
    {
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
            ->willReturn(null);

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
    }

    /**
     * @param $methodName
     * @param $templateName
     * @param $sendEmailResult
     *
     * @dataProvider sendEmailResultProvider
     */
    public function testSendEmail($methodName, $templateName, $sendEmailResult)
    {
        $this->objectRepository->expects($this->once())
            ->method('findByName')
            ->with($templateName)
            ->willReturn($this->getMock('Oro\Bundle\EmailBundle\Model\EmailTemplateInterface'));

        $this->renderer->expects($this->once())
            ->method('compileMessage');

        $this->mailProcessor->expects($this->once())
            ->method('sendEmail')
            ->willReturn($sendEmailResult);

        $this->assertEquals($sendEmailResult, $this->mailProcessor->{$methodName}(new User()));
    }

    public function sendEmailResultProvider()
    {
        return [
            [
                'sendChangePasswordEmail',
                Processor::TEMPLATE_USER_CHANGE_PASSWORD,
                true,
            ],
            [
                'sendChangePasswordEmail',
                Processor::TEMPLATE_USER_CHANGE_PASSWORD,
                false,
            ],
            [
                'sendResetPasswordEmail',
                Processor::TEMPLATE_USER_RESET_PASSWORD,
                true,
            ],
            [
                'sendResetPasswordEmail',
                Processor::TEMPLATE_USER_RESET_PASSWORD,
                false,
            ],
            [
                'sendResetPasswordAsAdminEmail',
                Processor::TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN,
                true,
            ],
            [
                'sendResetPasswordAsAdminEmail',
                Processor::TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN,
                false,
            ],
        ];
    }
}

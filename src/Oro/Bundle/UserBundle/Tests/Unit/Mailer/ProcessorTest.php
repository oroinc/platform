<?php

namespace Oro\Bundle\UserBundle\Tests\Mailer;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\UserBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Entity\User;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectRepository;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var EmailRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $renderer;

    /**
     * @var Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailProcessor;

    protected function setUp()
    {
        $this->objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->objectRepository);

        $this->renderer = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRenderer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mailProcessor = $this->getMockBuilder('Oro\Bundle\UserBundle\Mailer\Processor')
            ->setConstructorArgs([$this->objectManager, null, $this->renderer, null, null, null])
            ->setMethods(['sendEmail'])
            ->getMock();
    }

    public function testError()
    {
        $this->mailProcessor->setError('error');
        $this->assertTrue($this->mailProcessor->hasError());
        $this->assertEquals('error', $this->mailProcessor->getError());
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
            ->with($templateName);

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
                Processor::TEMPLATE_USER_RESET_PASSWORD,
                true,
            ],
            [
                'sendChangePasswordEmail',
                Processor::TEMPLATE_USER_RESET_PASSWORD,
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
                'sendResetPasswordEmailAsAdmin',
                Processor::TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN,
                true,
            ],
            [
                'sendResetPasswordEmailAsAdmin',
                Processor::TEMPLATE_USER_RESET_PASSWORD_AS_ADMIN,
                false,
            ],
        ];
    }
}

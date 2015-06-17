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

        $this->renderer = $this->getMockForClass('Oro\Bundle\EmailBundle\Provider\EmailRenderer');
        $this->renderer->expects($this->any())
            ->method('compileMessage')
            ->willReturn($this->templateData);

        $this->emailHolderHelper = $this->getMockForClass('Oro\Bundle\EmailBundle\Tools\EmailHolderHelper');

        $this->mailer = $this->getMockForClass('\Swift_Mailer');

        $this->mailProcessor = $this->getMockBuilder('Oro\Bundle\UserBundle\Mailer\BaseProcessor')
            ->setConstructorArgs(
                [
                    $this->managerRegistry,
                    $this->configManager,
                    $this->renderer,
                    $this->emailHolderHelper,
                    $this->mailer
                ]
            )
            ->setMethods(['sendEmail'])
            ->getMock();

        $this->emailTemplate = $this->getMock('Oro\Bundle\EmailBundle\Model\EmailTemplateInterface');
    }

    protected function tearDown()
    {
        unset($this->user, $this->templateData, $this->objectRepository, $this->objectManager, $this->managerRegistry);
        unset($this->configManager, $this->renderer, $this->emailHolderHelper, $this->mailer, $this->mailProcessor);
        unset($this->emailTemplate);
    }

    /**
     * @param string  $templateName
     * @param string  $getTypeResult
     * @param string  $typeValue
     * @param boolean $sendEmailResult
     *
     * @dataProvider sendEmailResultProvider
     */
    public function testSendEmail($templateName, $getTypeResult, $typeValue, $sendEmailResult)
    {
        $this->objectRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $templateName])
            ->willReturn($this->emailTemplate);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn($getTypeResult);

        $this->mailProcessor->expects($this->once())
            ->method('sendEmail')
            ->with($this->user, $this->templateData, $typeValue)
            ->willReturn($sendEmailResult);

        $this->assertEquals(
            $sendEmailResult,
            $this->mailProcessor->getEmailTemplateAndSendEmail($this->user, $templateName)
        );
    }

    /**
     * @return array
     */
    public function sendEmailResultProvider()
    {
        return [
            [
                'test_email',
                'txt',
                'text/plain',
                true
            ],
            [
                'test_email',
                'html',
                'text/html',
                false
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

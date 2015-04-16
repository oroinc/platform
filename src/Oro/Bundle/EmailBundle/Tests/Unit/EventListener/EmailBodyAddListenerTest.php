<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EmailBundle\EventListener\EmailBodyAddListener;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailBodyAddListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailBodyAddListener */
    protected $listener;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var EmailAttachmentManager */
    protected $emailAttachmentManager;

    /** @var EmailActivityListProvider */
    protected $activityListProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ServiceLink */
    protected $securityFacadeLink;

    public function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();
        $this->emailAttachmentManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager')
            ->disableOriginalConstructor()->getMock();
        $this->activityListProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider')
            ->disableOriginalConstructor()->getMock();
        $this->securityFacadeLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();
        $this->securityFacadeLink->expects($this->once())
            ->method('getService')
            ->willReturn($this->securityFacade);

        $this->listener = new EmailBodyAddListener(
            $this->emailAttachmentManager,
            $this->configProvider,
            $this->activityListProvider,
            $this->securityFacadeLink
        );
    }

    public function testLinkToScopeEventIsNotGranted()
    {
        $event = $this->getMockBuilder('Oro\Bundle\EmailBundle\Event\EmailBodyAdded')
            ->disableOriginalConstructor()->getMock();

        $this->securityFacade->expects($this->once())
            ->method('getToken')
            ->willReturn(1);
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);
        $this->activityListProvider->expects($this->never())
            ->method('getTargetEntities')
            ->willReturn([new SomeEntity()]);

        $this->listener->linkToScopeEvent($event);
    }

    /**
     * @dataProvider getTestData
     */
    public function testLinkToScopeEvent($config, $managerCalls, $attachmentCalls)
    {
        $attachments = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailAttachment')
            ->disableOriginalConstructor()->getMock();
        $emailBody = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailBody')
            ->disableOriginalConstructor()->getMock();
        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()->getMock();
        $event = $this->getMockBuilder('Oro\Bundle\EmailBundle\Event\EmailBodyAdded')
            ->disableOriginalConstructor()->getMock();
        $configInterface = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()->getMock();

        $this->securityFacade->expects($this->once())
            ->method('getToken')
            ->willReturn(1);
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);
        $this->activityListProvider->expects($this->once())
            ->method('getTargetEntities')
            ->willReturn([new SomeEntity()]);

        $configInterface->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));
        $this->configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($configInterface));

        $this->emailAttachmentManager
            ->expects($this->exactly($managerCalls))
            ->method('linkEmailAttachmentToTargetEntity');
        $emailBody->expects($this->exactly($attachmentCalls))
            ->method('getAttachments')
            ->will($this->returnValue([$attachments]));
        $email->expects($this->exactly($attachmentCalls))
            ->method('getEmailBody')
            ->will($this->returnValue($emailBody));
        $event->expects($this->exactly(1))
            ->method('getEmail')
            ->will($this->returnValue($email));

        $this->listener->linkToScopeEvent($event);
    }

    public function getTestData()
    {
        return [
            'link to scope if number true' => [
                'config' => 1,
                'managerCalls' => 1,
                'attachmentCalls' => 1
            ],
            'do not link to scope number false' => [
                'config' => 0,
                'managerCalls' => 0,
                'attachmentCalls' => 0
            ],
            'link to scope if true' => [
                'config' => true,
                'managerCalls' => 1,
                'attachmentCalls' => 1
            ],
            'do not link to scope if false' => [
                'config' => false,
                'managerCalls' => 0,
                'attachmentCalls' => 0
            ]
        ];
    }
}

<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Event\EmailBodySyncAfter;
use Oro\Bundle\EmailBundle\EventListener\EmailBodySyncListener;
use Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager;

class EmailBodySyncListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailBodySyncListener */
    protected $listener;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EmailAttachmentManager */
    protected $emailAttachmentManager;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->emailAttachmentManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Manager\EmailAttachmentManager')
            ->disableOriginalConstructor()->getMock();
        $this->listener = new EmailBodySyncListener($this->emailAttachmentManager, $this->configManager);
    }

    /**
     * @dataProvider getTestData
     */
    public function testLinkToScopeEvent($config, $managerCalls)
    {
        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()->getMock();
        $event = $this->getMockBuilder('Oro\Bundle\EmailBundle\Event\EmailBodySyncAfter')
            ->disableOriginalConstructor()->getMock();

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_email.link_email_attachments_to_scope_entity')
            ->will($this->returnValue($config));
        $this->emailAttachmentManager
            ->expects($this->exactly($managerCalls))
            ->method('linkEmailAttachmentsToTargetEntities')
            ->with($email);
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
                'managerCalls' => 1
            ],
            'do not link to scope number false' => [
                'config' => 0,
                'managerCalls' => 0
            ],
            'link to scope if true' => [
                'config' => true,
                'managerCalls' => 1
            ],
            'do not link to scope if false' => [
                'config' => false,
                'managerCalls' => 0
            ],
        ];
    }
}

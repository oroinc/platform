<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EntityConfig;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentConfig;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AttachmentConfigTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var AttachmentConfig */
    protected $attachmentConfig;

    protected function setUp()
    {
        $this->attachmentConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentConfig = new AttachmentConfig($this->attachmentConfigProvider, $this->entityConfigProvider);
    }

    public function testIsAttachmentAssociationEnabledWithNull()
    {
        $this->attachmentConfigProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->attachmentConfig->isAttachmentAssociationEnabled(null)
        );
    }

    public function testIsAttachmentAssociationEnabledWithNotObject()
    {
        $this->attachmentConfigProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->attachmentConfig->isAttachmentAssociationEnabled('test')
        );
    }

    public function testIsAttachmentAssociationEnabledWithNotConfigurableEntity()
    {
        $this->attachmentConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('stdClass')
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->attachmentConfig->isAttachmentAssociationEnabled(new \stdClass())
        );
    }

    public function testIsNoteAssociationEnabledWithNotUpdatedSchema()
    {
        $config = new Config(new EntityConfigId('attachment', 'stdClass'));
        $config->set('enabled', true);

        $this->attachmentConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('stdClass')
            ->will($this->returnValue(true));
        $this->attachmentConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('stdClass')
            ->will($this->returnValue($config));
        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(AttachmentScope::ATTACHMENT, ExtendHelper::buildAssociationName('stdClass'))
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->attachmentConfig->isAttachmentAssociationEnabled(new \stdClass())
        );
    }

    public function testIsAttachmentAssociationEnabled()
    {
        $config = new Config(new EntityConfigId('attachment', 'stdClass'));
        $config->set('enabled', true);

        $this->attachmentConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('stdClass')
            ->will($this->returnValue(true));
        $this->attachmentConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('stdClass')
            ->will($this->returnValue($config));
        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(AttachmentScope::ATTACHMENT, ExtendHelper::buildAssociationName('stdClass'))
            ->will($this->returnValue(true));

        $this->assertTrue(
            $this->attachmentConfig->isAttachmentAssociationEnabled(new \stdClass())
        );
    }
}

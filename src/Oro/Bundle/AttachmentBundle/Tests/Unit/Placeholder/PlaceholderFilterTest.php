<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\AttachmentBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var PlaceholderFilter */
    protected $filter;

    protected function setUp()
    {
        $this->attachmentConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new PlaceholderFilter($this->attachmentConfigProvider, $this->entityConfigProvider);
    }

    public function testIsAttachmentAssociationEnabledWithNull()
    {
        $this->attachmentConfigProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->filter->isAttachmentAssociationEnabled(null)
        );
    }

    public function testIsAttachmentAssociationEnabledWithNotObject()
    {
        $this->attachmentConfigProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->filter->isAttachmentAssociationEnabled('test')
        );
    }

    public function testIsAttachmentAssociationEnabledWithNotConfigurableEntity()
    {
        $this->attachmentConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('stdClass')
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->filter->isAttachmentAssociationEnabled(new \stdClass())
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
            $this->filter->isAttachmentAssociationEnabled(new \stdClass())
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
            $this->filter->isAttachmentAssociationEnabled(new \stdClass())
        );
    }
}

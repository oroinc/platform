<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\AttachmentBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $noteConfigProvider;

    /** @var PlaceholderFilter */
    protected $filter;

    protected function setUp()
    {
        $this->noteConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new PlaceholderFilter($this->noteConfigProvider);
    }

    public function testIsAttachmentAssociationEnabledWithNull()
    {
        $this->noteConfigProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->filter->isAttachmentAssociationEnabled(null)
        );
    }

    public function testIsAttachmentAssociationEnabledWithNotObject()
    {
        $this->noteConfigProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->filter->isAttachmentAssociationEnabled('test')
        );
    }

    public function testIsAttachmentAssociationEnabledWithNotConfigurableEntity()
    {
        $this->noteConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('stdClass')
            ->will($this->returnValue(false));

        $this->assertFalse(
            $this->filter->isAttachmentAssociationEnabled(new \stdClass())
        );
    }

    public function testIsAttachmentAssociationEnabled()
    {
        $config = new Config(new EntityConfigId('attachment', 'stdClass'));
        $config->set('enabled', true);

        $this->noteConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('stdClass')
            ->will($this->returnValue(true));
        $this->noteConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('stdClass')
            ->will($this->returnValue($config));

        $this->assertTrue(
            $this->filter->isAttachmentAssociationEnabled(new \stdClass())
        );
    }
}

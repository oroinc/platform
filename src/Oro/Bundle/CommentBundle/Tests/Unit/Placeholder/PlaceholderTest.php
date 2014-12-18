<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\CommentBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;

class PlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var  PlaceholderFilter */
    protected $filter;

    protected function setUp()
    {
        $this->configManager    = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter           = new PlaceholderFilter($this->configManager);
    }

    public function testIsApplicableWithEmptyObject()
    {
        $this->assertNull($this->filter->isApplicable(null));
    }

    public function testIsApplicable()
    {
        $config =  $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('is')
            ->with('enabled')
            ->will($this->returnValue(true));
        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $provider->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->will($this->returnValue($provider));

        $this->assertTrue($this->filter->isApplicable(new ItemStub()));
    }
}

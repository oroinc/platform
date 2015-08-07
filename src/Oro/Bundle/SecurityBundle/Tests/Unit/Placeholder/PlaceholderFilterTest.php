<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Placeholder;

use Oro\Bundle\SecurityBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var PlaceholderFilter */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new PlaceholderFilter($this->securityFacade, $this->configProvider);
    }

    public function testIsShareEnabledWithNull()
    {
        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $this->assertFalse(
            $this->filter->isShareEnabled(null)
        );
    }

    public function testIsShareEnabledWithNotObject()
    {
        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $this->assertFalse(
            $this->filter->isShareEnabled('test')
        );
    }

    public function testIsShareEnabledWithNotDeniedPermission()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $this->assertFalse(
            $this->filter->isShareEnabled(new \stdClass())
        );
    }

    public function testIsShareEnabledWithNotConfigurableEntity()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->willReturn(false);

        $this->assertFalse(
            $this->filter->isShareEnabled(new \stdClass())
        );
    }

    public function testIsShareEnabledWithNotShareScopes()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->willReturn(true);
        $this->config->expects($this->once())
            ->method('get')
            ->with('share_scopes')
            ->willReturn([]);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->config);

        $this->assertFalse(
            $this->filter->isShareEnabled(new \stdClass())
        );
    }

    public function testIsShareEnabled()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->willReturn(true);
        $this->config->expects($this->once())
            ->method('get')
            ->with('share_scopes')
            ->willReturn(['user']);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->config);

        $this->assertTrue(
            $this->filter->isShareEnabled(new \stdClass())
        );
    }
}

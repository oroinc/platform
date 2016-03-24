<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ApplicationsUrlHelper;

class ApplicationsUrlHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var ApplicationsHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $mockApplicationsHelper;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $mockRouter;

    /** @var ApplicationsUrlHelper */
    protected $instance;

    protected function setUp()
    {
        $this->mockApplicationsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockRouter = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $this->instance = new ApplicationsUrlHelper($this->mockApplicationsHelper, $this->mockRouter);
    }

    public function testGetExecutionUrl()
    {
        $parameters = ['param1' => 'val1'];

        $this->mockApplicationsHelper->expects($this->once())
            ->method('getExecutionRoute')
            ->willReturn('extension_route');

        $this->mockRouter->expects($this->once())
            ->method('generate')
            ->with('extension_route', $parameters)
            ->willReturn('ok_extension');

        $this->assertEquals('ok_extension', $this->instance->getExecutionUrl($parameters));
    }

    public function testGetDialogUrl()
    {
        $parameters = ['param1' => 'val1'];
        
        $this->mockApplicationsHelper->expects($this->once())
            ->method('getDialogRoute')
            ->willReturn('dialog_route');

        $this->mockRouter->expects($this->once())
            ->method('generate')
            ->with('dialog_route', $parameters)
            ->willReturn('ok_dialog');

        $this->assertEquals('ok_dialog', $this->instance->getDialogUrl($parameters));
    }
}

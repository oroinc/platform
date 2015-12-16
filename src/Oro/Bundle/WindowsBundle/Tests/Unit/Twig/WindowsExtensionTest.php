<?php

namespace Oro\Bundle\WindowsBundle\Tests\Twig;

use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManagerRegistry;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateRequestManager;
use Oro\Bundle\WindowsBundle\Entity\WindowsState;
use Oro\Bundle\WindowsBundle\Twig\WindowsExtension;

class WindowsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WindowsExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment
     */
    protected $environment;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WindowsStateManager
     */
    protected $stateManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WindowsStateManagerRegistry
     */
    protected $stateManagerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WindowsStateRequestManager
     */
    protected $requestStateManager;

    protected function setUp()
    {
        $this->environment = $this->getMockBuilder('Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $this->stateManager = $this->getMockBuilder('Oro\Bundle\WindowsBundle\Manager\WindowsStateManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->stateManagerRegistry = $this
            ->getMockBuilder('Oro\Bundle\WindowsBundle\Manager\WindowsStateManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->stateManagerRegistry->expects($this->any())->method('getManager')->willReturn($this->stateManager);
        $this->requestStateManager = $this
            ->getMockBuilder('Oro\Bundle\WindowsBundle\Manager\WindowsStateRequestManager')
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new WindowsExtension($this->stateManagerRegistry, $this->requestStateManager);
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('oro_windows_restore', $functions);
        $this->assertInstanceOf('Twig_Function_Method', $functions['oro_windows_restore']);
        $this->assertAttributeEquals('render', 'method', $functions['oro_windows_restore']);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_windows', $this->extension->getName());
    }

    public function testRenderNoUser()
    {
        $this->stateManager->expects($this->once())->method('getWindowsStates')
            ->willThrowException(new AccessDeniedException());

        $this->assertEmpty($this->extension->render($this->environment));
    }

    public function testRender()
    {
        $windowStateFoo = $this->createWindowState(['cleanUrl' => 'foo']);
        $windowStateBar = $this->createWindowState(['cleanUrl' => 'foo']);

        $windowStates = [$windowStateFoo, $windowStateBar];

        $this->stateManager->expects($this->once())
            ->method('getWindowsStates')
            ->will($this->returnValue($windowStates));

        $expectedOutput = 'RENDERED';
        $this->environment->expects($this->once())
            ->method('render')
            ->with(
                'OroWindowsBundle::states.html.twig',
                ['windowStates' => [$windowStateFoo, $windowStateBar]]
            )
            ->will($this->returnValue($expectedOutput));

        $this->assertEquals($expectedOutput, $this->extension->render($this->environment));

        // no need to render twice
        $this->assertEquals('', $this->extension->render($this->environment));
    }

    /**
     * @param string $cleanUrl
     * @param string $type
     * @param string $expectedUrl
     * @dataProvider renderFragmentDataProvider
     */
    public function testRenderFragment($cleanUrl, $type, $expectedUrl)
    {
        $windowState = $this->createWindowState(['cleanUrl' => $cleanUrl, 'type' => $type]);

        $httpKernelExtension = $this->getHttpKernelExtensionMock();

        $this->environment->expects($this->once())
            ->method('getExtension')
            ->with('http_kernel')
            ->will($this->returnValue($httpKernelExtension));

        $expectedOutput = 'RENDERED';
        $httpKernelExtension->expects($this->once())
            ->method('renderFragment')
            ->with(
                $this->callback(
                    function ($url) use ($expectedUrl) {
                        $count = 0;
                        $cleanUrl = preg_replace('/&_wid=([a-z0-9]*)-([a-z0-9]*)/', '', $url, -1, $count);

                        return ($count === 1 && $cleanUrl === $expectedUrl);
                    }
                )
            )
            ->will($this->returnValue($expectedOutput));

        $this->assertEquals($expectedOutput, $this->extension->renderFragment($this->environment, $windowState));
        $this->assertTrue($windowState->isRenderedSuccessfully());
    }

    /**
     * @return array
     */
    public function renderFragmentDataProvider()
    {
        return [
            'url_without_parameters' => [
                'widgetUrl' => '/user/create',
                'widgetType' => 'test',
                'expectedWidgetUrl' => '/user/create?_widgetContainer=test',
            ],
            'url_with_parameters' => [
                'widgetUrl' => '/user/create?id=1',
                'widgetType' => 'test',
                'expectedWidgetUrl' => '/user/create?id=1&_widgetContainer=test',
            ],
            'url_with_parameters_and_fragment' => [
                'widgetUrl' => '/user/create?id=1#group=date',
                'widgetType' => 'test',
                'expectedWidgetUrl' => '/user/create?id=1&_widgetContainer=test#group=date',
            ],
        ];
    }

    public function testRenderFragmentWithNotFoundHttpException()
    {
        $cleanUrl = '/foo/bar';
        $windowState = $this->createWindowState(['cleanUrl' => $cleanUrl]);

        $httpKernelExtension = $this->getHttpKernelExtensionMock();

        $this->environment->expects($this->once())
            ->method('getExtension')
            ->with('http_kernel')
            ->will($this->returnValue($httpKernelExtension));

        $httpKernelExtension->expects($this->once())
            ->method('renderFragment')
            ->with($cleanUrl)
            ->will($this->throwException(new NotFoundHttpException()));

        $this->stateManager->expects($this->once())
            ->method('deleteWindowsState')
            ->with($windowState->getId());

        $this->assertEquals('', $this->extension->renderFragment($this->environment, $windowState));
        $this->assertFalse($windowState->isRenderedSuccessfully());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage This is exception was not caught.
     */
    public function testRenderFragmentWithGenericException()
    {
        $cleanUrl = '/foo/bar';
        $windowState = $this->createWindowState(['cleanUrl' => $cleanUrl]);

        $httpKernelExtension = $this->getHttpKernelExtensionMock();

        $this->environment->expects($this->once())
            ->method('getExtension')
            ->with('http_kernel')
            ->will($this->returnValue($httpKernelExtension));

        $httpKernelExtension->expects($this->once())
            ->method('renderFragment')
            ->with($cleanUrl)
            ->will($this->throwException(new \Exception('This is exception was not caught.')));

        $this->extension->renderFragment($this->environment, $windowState);
    }

    public function testRenderFragmentWithEmptyCleanUrl()
    {
        $windowState = $this->createWindowState();

        $this->environment->expects($this->never())->method($this->anything());

        $this->assertEquals('', $this->extension->renderFragment($this->environment, $windowState));
        $this->assertFalse($windowState->isRenderedSuccessfully());
    }

    public function testRenderFragmentWithoutUser()
    {
        $windowState = $this->createWindowState();

        $this->environment->expects($this->never())->method($this->anything());

        $this->stateManager->expects($this->once())->method('deleteWindowsState')
            ->willThrowException(new AccessDeniedException());

        $this->assertEquals('', $this->extension->renderFragment($this->environment, $windowState));
        $this->assertFalse($windowState->isRenderedSuccessfully());
    }

    /**
     * @param array $data
     * @return WindowsState
     */
    protected function createWindowState(array $data = [])
    {
        $state = new WindowsState();
        $state->setData($data);

        return $state;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|HttpKernelExtension
     */
    protected function getHttpKernelExtensionMock()
    {
        return $this->getMockBuilder('Symfony\Bridge\Twig\Extension\HttpKernelExtension')
            ->disableOriginalConstructor()
            ->getMock();
    }
}

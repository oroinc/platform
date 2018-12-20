<?php

namespace Oro\Bundle\WindowsBundle\Tests\Twig;

use Oro\Bundle\WindowsBundle\Entity\WindowsState;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManagerRegistry;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateRequestManager;
use Oro\Bundle\WindowsBundle\Twig\WindowsExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class WindowsExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var WindowsExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Twig_Environment */
    protected $environment;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WindowsStateManager */
    protected $stateManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WindowsStateManagerRegistry */
    protected $stateManagerRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WindowsStateRequestManager */
    protected $requestStateManager;

    /** @var  FragmentHandler|\PHPUnit\Framework\MockObject\MockObject */
    protected $fragmentHandler;

    protected function setUp()
    {
        $this->environment = $this->getMockBuilder(\Twig_Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stateManager = $this->getMockBuilder(WindowsStateManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stateManagerRegistry = $this->getMockBuilder(WindowsStateManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stateManagerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturn($this->stateManager);
        $this->requestStateManager = $this->getMockBuilder(WindowsStateRequestManager::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fragmentHandler = $this->createMock(FragmentHandler::class);

        $container = self::getContainerBuilder()
            ->add('oro_windows.manager.windows_state_registry', $this->stateManagerRegistry)
            ->add('oro_windows.manager.windows_state_request', $this->requestStateManager)
            ->add('fragment.handler', $this->fragmentHandler)
            ->getContainer($this);

        $this->extension = new WindowsExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_windows', $this->extension->getName());
    }

    public function testRenderNoUser()
    {
        $this->stateManager->expects($this->once())
            ->method('getWindowsStates')
            ->willThrowException(new AccessDeniedException());

        $this->assertEmpty(
            self::callTwigFunction($this->extension, 'oro_windows_restore', [$this->environment])
        );
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

        $this->assertEquals(
            $expectedOutput,
            self::callTwigFunction($this->extension, 'oro_windows_restore', [$this->environment])
        );

        // no need to render twice
        $this->assertEquals(
            '',
            self::callTwigFunction($this->extension, 'oro_windows_restore', [$this->environment])
        );
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

        $expectedOutput = 'RENDERED';
        $this->fragmentHandler->expects($this->once())
            ->method('render')
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

        $this->assertEquals(
            $expectedOutput,
            self::callTwigFunction($this->extension, 'oro_window_render_fragment', [$this->environment, $windowState])
        );
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

        $this->fragmentHandler->expects($this->once())
            ->method('render')
            ->with($cleanUrl)
            ->will($this->throwException(new NotFoundHttpException()));

        $this->stateManager->expects($this->once())
            ->method('deleteWindowsState')
            ->with($windowState->getId());

        $this->assertEquals(
            '',
            self::callTwigFunction($this->extension, 'oro_window_render_fragment', [$this->environment, $windowState])
        );
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

        $this->fragmentHandler->expects($this->once())
            ->method('render')
            ->with($cleanUrl)
            ->will($this->throwException(new \Exception('This is exception was not caught.')));

        self::callTwigFunction($this->extension, 'oro_window_render_fragment', [$this->environment, $windowState]);
    }

    public function testRenderFragmentWithEmptyCleanUrl()
    {
        $windowState = $this->createWindowState();

        $this->environment->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            '',
            self::callTwigFunction($this->extension, 'oro_window_render_fragment', [$this->environment, $windowState])
        );
        $this->assertFalse($windowState->isRenderedSuccessfully());
    }

    public function testRenderFragmentWithoutUser()
    {
        $windowState = $this->createWindowState();

        $this->environment->expects($this->never())->method($this->anything());

        $this->stateManager->expects($this->once())
            ->method('deleteWindowsState')
            ->willThrowException(new AccessDeniedException());

        $this->assertEquals(
            '',
            self::callTwigFunction($this->extension, 'oro_window_render_fragment', [$this->environment, $windowState])
        );
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
}

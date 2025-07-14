<?php

namespace Oro\Bundle\WindowsBundle\Tests\Unit\Twig;

use Oro\Bundle\WindowsBundle\Entity\WindowsState;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManagerRegistry;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateRequestManager;
use Oro\Bundle\WindowsBundle\Twig\WindowsExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Twig\Environment;

class WindowsExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;
    use EntityTrait;

    private WindowsExtension $extension;
    private Environment&MockObject $environment;
    private WindowsStateManager&MockObject $stateManager;
    private WindowsStateManagerRegistry&MockObject $stateManagerRegistry;
    private WindowsStateRequestManager $requestStateManager;
    private FragmentHandler&MockObject $fragmentHandler;

    #[\Override]
    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);
        $this->stateManager = $this->createMock(WindowsStateManager::class);
        $this->stateManagerRegistry = $this->createMock(WindowsStateManagerRegistry::class);
        $this->requestStateManager = new WindowsStateRequestManager($this->createMock(RequestStack::class));
        $this->fragmentHandler = $this->createMock(FragmentHandler::class);

        $container = self::getContainerBuilder()
            ->add('oro_windows.manager.windows_state_registry', $this->stateManagerRegistry)
            ->add('oro_windows.manager.windows_state_request', $this->requestStateManager)
            ->add('fragment.handler', $this->fragmentHandler)
            ->getContainer($this);

        $this->extension = new WindowsExtension($container);
    }

    private function createWindowState(array $data = [], ?int $id = 123): WindowsState
    {
        $state = $this->getEntity(WindowsState::class, ['id' => $id]);
        $state->setData($data);

        return $state;
    }

    public function testRenderNoUser(): void
    {
        $this->stateManagerRegistry->expects($this->once())
            ->method('getManager')
            ->willReturn(null);
        $this->stateManager->expects($this->never())
            ->method('getWindowsStates');

        $this->assertEmpty(
            self::callTwigFunction($this->extension, 'oro_windows_restore', [$this->environment])
        );
    }

    public function testRender(): void
    {
        $windowStateFoo = $this->createWindowState(['cleanUrl' => 'foo']);
        $windowStateBar = $this->createWindowState(['cleanUrl' => 'foo']);

        $windowStates = [$windowStateFoo, $windowStateBar];

        $this->stateManagerRegistry->expects($this->once())
            ->method('getManager')
            ->willReturn($this->stateManager);
        $this->stateManager->expects($this->once())
            ->method('getWindowsStates')
            ->willReturn($windowStates);

        $expectedOutput = 'RENDERED';
        $this->environment->expects($this->once())
            ->method('render')
            ->with(
                '@OroWindows/states.html.twig',
                ['windowStates' => [$windowStateFoo, $windowStateBar]]
            )
            ->willReturn($expectedOutput);

        $this->assertEquals(
            $expectedOutput,
            self::callTwigFunction($this->extension, 'oro_windows_restore', [$this->environment])
        );

        // no need to render twice
        $this->assertSame(
            '',
            self::callTwigFunction($this->extension, 'oro_windows_restore', [$this->environment])
        );
    }

    /**
     * @dataProvider renderFragmentDataProvider
     */
    public function testRenderFragment(string $cleanUrl, string $type, string $expectedUrl): void
    {
        $windowState = $this->createWindowState(['cleanUrl' => $cleanUrl, 'type' => $type]);

        $expectedOutput = 'RENDERED';
        $this->fragmentHandler->expects($this->once())
            ->method('render')
            ->with($this->callback(function ($url) use ($expectedUrl) {
                $count = 0;
                $cleanUrl = preg_replace('/&_wid=([a-z0-9]*)-([a-z0-9]*)/', '', $url, -1, $count);

                return ($count === 1 && $cleanUrl === $expectedUrl);
            }))
            ->willReturn($expectedOutput);

        $this->stateManagerRegistry->expects($this->never())
            ->method('getManager');

        $this->assertEquals(
            $expectedOutput,
            self::callTwigFunction($this->extension, 'oro_window_render_fragment', [$windowState])
        );
        $this->assertTrue($windowState->isRenderedSuccessfully());
    }

    public function renderFragmentDataProvider(): array
    {
        return [
            'url_without_parameters'           => [
                'widgetUrl'         => '/user/create',
                'widgetType'        => 'test',
                'expectedWidgetUrl' => '/user/create?_widgetContainer=test',
            ],
            'url_with_parameters'              => [
                'widgetUrl'         => '/user/create?id=1',
                'widgetType'        => 'test',
                'expectedWidgetUrl' => '/user/create?id=1&_widgetContainer=test',
            ],
            'url_with_parameters_and_fragment' => [
                'widgetUrl'         => '/user/create?id=1#group=date',
                'widgetType'        => 'test',
                'expectedWidgetUrl' => '/user/create?id=1&_widgetContainer=test#group=date',
            ],
        ];
    }

    public function testRenderFragmentWithNotFoundHttpException(): void
    {
        $cleanUrl = '/foo/bar';
        $windowState = $this->createWindowState(['cleanUrl' => $cleanUrl]);

        $this->fragmentHandler->expects($this->once())
            ->method('render')
            ->with($cleanUrl)
            ->willThrowException(new NotFoundHttpException());

        $this->stateManagerRegistry->expects($this->once())
            ->method('getManager')
            ->willReturn($this->stateManager);
        $this->stateManager->expects($this->once())
            ->method('deleteWindowsState')
            ->with($windowState->getId());

        $this->assertSame(
            '',
            self::callTwigFunction($this->extension, 'oro_window_render_fragment', [$windowState])
        );
        $this->assertFalse($windowState->isRenderedSuccessfully());
    }

    public function testRenderFragmentWithGenericException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Not caught exception.');

        $cleanUrl = '/foo/bar';
        $windowState = $this->createWindowState(['cleanUrl' => $cleanUrl]);

        $this->fragmentHandler->expects($this->once())
            ->method('render')
            ->with($cleanUrl)
            ->willThrowException(new \Exception('Not caught exception.'));

        $this->stateManagerRegistry->expects($this->never())
            ->method('getManager');

        try {
            self::callTwigFunction($this->extension, 'oro_window_render_fragment', [$windowState]);
        } catch (\Exception $e) {
            $this->assertFalse($windowState->isRenderedSuccessfully());
            throw $e;
        }
    }

    public function testRenderFragmentWithEmptyCleanUrl(): void
    {
        $windowState = $this->createWindowState();

        $this->fragmentHandler->expects($this->never())
            ->method('render');

        $this->stateManagerRegistry->expects($this->once())
            ->method('getManager')
            ->willReturn($this->stateManager);
        $this->stateManager->expects($this->once())
            ->method('deleteWindowsState')
            ->with($windowState->getId());

        $this->assertSame(
            '',
            self::callTwigFunction($this->extension, 'oro_window_render_fragment', [$windowState])
        );
        $this->assertFalse($windowState->isRenderedSuccessfully());
    }

    public function testRenderFragmentWithEmptyCleanUrlAndWithoutUser(): void
    {
        $windowState = $this->createWindowState();

        $this->fragmentHandler->expects($this->never())
            ->method('render');

        $this->stateManagerRegistry->expects($this->once())
            ->method('getManager')
            ->willReturn(null);
        $this->stateManager->expects($this->never())
            ->method('deleteWindowsState');

        $this->assertSame(
            '',
            self::callTwigFunction($this->extension, 'oro_window_render_fragment', [$windowState])
        );
        $this->assertFalse($windowState->isRenderedSuccessfully());
    }
}

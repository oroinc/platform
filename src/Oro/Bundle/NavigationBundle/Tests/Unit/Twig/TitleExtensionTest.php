<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use Oro\Bundle\NavigationBundle\Twig\TitleExtension;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TitleExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var TitleServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $titleService;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var TitleExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->titleService = $this->createMock(TitleServiceInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $container = self::getContainerBuilder()
            ->add('oro_navigation.title_service', $this->titleService)
            ->add(RequestStack::class, $this->requestStack)
            ->getContainer($this);

        $this->extension = new TitleExtension($container);
    }

    public function testRenderSerialized()
    {
        $expectedResult = 'expected';
        $routeName = 'test_route';

        $this->setRouteName($routeName);

        $this->titleService->expects(self::once())
            ->method('loadByRoute')
            ->with($routeName)
            ->willReturnSelf();
        $this->titleService->expects(self::once())
            ->method('setData')
            ->willReturnSelf();
        $this->titleService->expects(self::once())
            ->method('getSerialized')
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_title_render_serialized', [])
        );
    }

    public function testRender()
    {
        $expectedResult = 'expected';
        $title = 'title';
        $routeName = 'test_route';

        $this->setRouteName($routeName);

        $this->titleService->expects(self::once())
            ->method('loadByRoute')
            ->with($routeName)
            ->willReturnSelf();
        $this->titleService->expects(self::once())
            ->method('setData')
            ->with([])
            ->willReturnSelf();
        $this->titleService->expects(self::once())
            ->method('render')
            ->with([], $title, null, null, true)
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_title_render', [$title])
        );
    }

    public function testRenderShort()
    {
        $expectedResult = 'expected';
        $title = 'title';
        $routeName = 'test_route';

        $this->setRouteName($routeName);

        $this->titleService->expects(self::once())
            ->method('loadByRoute')
            ->with($routeName)
            ->willReturnSelf();
        $this->titleService->expects(self::once())
            ->method('setData')
            ->with([])
            ->willReturnSelf();
        $this->titleService->expects(self::once())
            ->method('render')
            ->with([], $title, null, null, true, true)
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_title_render_short', [$title])
        );
    }

    /**
     * @dataProvider renderAfterSetDataProvider
     */
    public function testRenderAfterSet(array $data, array $expectedData)
    {
        foreach ($data as $arguments) {
            [$data, $templateScope] = array_pad($arguments, 2, null);
            $this->extension->set($data, $templateScope);
        }

        $expectedResult = 'expected';
        $title = 'test';
        $routeName = 'test_route';

        $this->setRouteName($routeName);

        $this->titleService->expects(self::once())
            ->method('loadByRoute')
            ->with($routeName)
            ->willReturnSelf();
        $this->titleService->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($expectedData))
            ->willReturnSelf();
        $this->titleService->expects(self::once())
            ->method('render')
            ->with([], $title, null, null, true)
            ->willReturn($expectedResult);

        self::assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_title_render', [$title])
        );
    }

    public function renderAfterSetDataProvider(): array
    {
        return [
            'override options in same template' => [
                [
                    [['k1' => 'v1']],
                    [['k1' => 'v2']],
                    [['k2' => 'v3']],
                ],
                ['k1' => 'v2', 'k2' => 'v3'],
            ],
            'override options in different template' => [
                [
                    [['k1' => 'v1'], 'child_template'],
                    [['k1' => 'v2'], 'child_template'],
                    [['k3' => 'v3'], 'child_template'],
                    [['k5' => ['k1' => 'v2', 'k2' => 'val2']], 'child_template'],
                    [['k1' => 'v4'], 'parent_template'],
                    [['k2' => 'v5'], 'parent_template'],
                    [['k3' => 'v6'], 'parent_template'],
                    [['k4' => 'v7'], 'parent_template'],
                    [['k5' => ['k1' => 'v1', 'k3' => 'val3']], 'parent_template'],
                ],
                [
                    'k1' => 'v2',
                    'k2' => 'v5',
                    'k3' => 'v3',
                    'k4' => 'v7',
                    'k5' => ['k1' => 'v2', 'k3' => 'val3', 'k2' => 'val2']
                ],
            ],
            'empty data' => [
                [],
                [],
            ],
        ];
    }

    public function testSet()
    {
        $fooData = ['k' => 'foo'];
        $barData = ['k' => 'bar'];

        $this->titleService->expects(self::never())
            ->method('setData');

        $this->extension->set($fooData);
        $this->extension->set($barData);

        self::assertEquals(
            [md5(__FILE__) => [$fooData, $barData]],
            ReflectionUtil::getPropertyValue($this->extension, 'templateFileTitleDataStack')
        );
    }

    public function testTokenParserDeclarations()
    {
        $result = $this->extension->getTokenParsers();

        self::assertIsArray($result);
        self::assertCount(1, $result);
    }

    private function setRouteName(string $routeName): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::any())
            ->method('get')
            ->with('_route')
            ->willReturn($routeName);

        $this->requestStack->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($request);
    }
}

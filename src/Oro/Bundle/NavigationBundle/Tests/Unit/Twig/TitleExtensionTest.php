<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use Oro\Bundle\NavigationBundle\Twig\TitleExtension;
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

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->titleService = $this->getMockBuilder(TitleServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_navigation.title_service', $this->titleService)
            ->add('request_stack', $this->requestStack)
            ->getContainer($this);

        $this->extension = new TitleExtension($container);
    }

    public function testNameConfigured()
    {
        $this->assertInternalType('string', $this->extension->getName());
    }

    public function testRenderSerialized()
    {
        $expectedResult = 'expected';
        $routeName = 'test_route';

        $this->setRouteName($routeName);

        $this->titleService->expects($this->at(0))
            ->method('loadByRoute')
            ->with($routeName)
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(1))
            ->method('setData')
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(2))
            ->method('getSerialized')
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
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

        $this->titleService->expects($this->at(0))
            ->method('loadByRoute')
            ->with($routeName)
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(1))
            ->method('setData')
            ->with([])
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(2))
            ->method('render')
            ->with([], $title, null, null, true)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
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

        $this->titleService->expects($this->at(0))
            ->method('loadByRoute')
            ->with($routeName)
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(1))
            ->method('setData')
            ->with([])
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(2))
            ->method('render')
            ->with([], $title, null, null, true, true)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_title_render_short', [$title])
        );
    }

    /**
     * @dataProvider renderAfterSetDataProvider
     * @param array $data
     * @param array $expectedData
     */
    public function testRenderAfterSet(array $data, array $expectedData)
    {
        foreach ($data as $arguments) {
            list($data, $templateScope) = array_pad($arguments, 2, null);
            $this->extension->set($data, $templateScope);
        }

        $expectedResult = 'expected';
        $title = 'test';
        $routeName = 'test_route';

        $this->setRouteName($routeName);

        $this->titleService->expects($this->at(0))
            ->method('loadByRoute')
            ->with($routeName)
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(1))
            ->method('setData')
            ->with($expectedData)
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(2))
            ->method('render')
            ->with([], $title, null, null, true)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_title_render', [$title])
        );
    }

    /**
     * @return array
     */
    public function renderAfterSetDataProvider()
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
                    [['k1' => 'v4'], 'parent_template'],
                    [['k2' => 'v5'], 'parent_template'],
                    [['k3' => 'v6'], 'parent_template'],
                    [['k4' => 'v7'], 'parent_template'],
                ],
                ['k1' => 'v2', 'k2' => 'v5', 'k3' => 'v3', 'k4' => 'v7'],
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

        $this->titleService->expects($this->never())
            ->method('setData');

        $this->extension->set($fooData);
        $this->extension->set($barData);

        $this->assertAttributeEquals(
            [
                md5(__FILE__) => [$fooData, $barData]
            ],
            'templateFileTitleDataStack',
            $this->extension
        );
    }

    public function testTokenParserDeclarations()
    {
        $result = $this->extension->getTokenParsers();

        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);
    }

    /**
     * @param string $routeName
     */
    protected function setRouteName($routeName)
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->expects($this->any())
            ->method('get')
            ->with('_route')
            ->willReturn($routeName);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);
    }
}

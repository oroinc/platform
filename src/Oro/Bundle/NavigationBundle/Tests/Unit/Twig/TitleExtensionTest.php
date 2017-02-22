<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Provider\TitleService;
use Oro\Bundle\NavigationBundle\Twig\TitleExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class TitleExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $titleService;

    /** @var TitleExtension */
    private $extension;

    protected function setUp()
    {
        $this->titleService = $this->getMockBuilder(TitleService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_navigation.title_service', $this->titleService)
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

        $this->titleService->expects($this->at(0))
            ->method('setData')
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(1))
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

        $this->titleService->expects($this->at(0))
            ->method('setData')
            ->with(array())
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(1))
            ->method('render')
            ->with(array(), $title, null, null, true)
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

        $this->titleService->expects($this->at(0))
            ->method('setData')
            ->with(array())
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(1))
            ->method('render')
            ->with(array(), $title, null, null, true, true)
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

        $this->titleService->expects($this->at(0))
            ->method('setData')
            ->with($expectedData)
            ->will($this->returnSelf());

        $this->titleService->expects($this->at(1))
            ->method('render')
            ->with(array(), $title, null, null, true)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_title_render', [$title])
        );
    }

    public function renderAfterSetDataProvider()
    {
        return array(
            'override options in same template' => array(
                array(
                    array(array('k1' => 'v1')),
                    array(array('k1' => 'v2')),
                    array(array('k2' => 'v3')),
                ),
                array('k1' => 'v2', 'k2' => 'v3'),
            ),
            'override options in different template' => array(
                array(
                    array(array('k1' => 'v1'), 'child_template'),
                    array(array('k1' => 'v2'), 'child_template'),
                    array(array('k3' => 'v3'), 'child_template'),
                    array(array('k1' => 'v4'), 'parent_template'),
                    array(array('k2' => 'v5'), 'parent_template'),
                    array(array('k3' => 'v6'), 'parent_template'),
                    array(array('k4' => 'v7'), 'parent_template'),
                ),
                array('k1' => 'v2', 'k2' => 'v5', 'k3' => 'v3', 'k4' => 'v7'),
            ),
            'empty data' => array(
                array(),
                array(),
            ),
        );
    }

    public function testSet()
    {
        $fooData = array('k' => 'foo');
        $barData = array('k' => 'bar');

        $this->titleService->expects($this->never())
            ->method('setData');

        $this->extension->set($fooData);
        $this->extension->set($barData);

        $this->assertAttributeEquals(
            array(
                md5(__FILE__) => array($fooData, $barData)
            ),
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
}

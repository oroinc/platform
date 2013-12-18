<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SidebarBundle\Twig\SidebarExtension;

class SidebarExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $widgetDefinitionsRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var SidebarExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->widgetDefinitionsRegistry = $this
            ->getMockBuilder('Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new SidebarExtension($this->widgetDefinitionsRegistry, $this->container);
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(1, $functions);

        $this->assertInstanceOf('\Twig_SimpleFunction', $functions[0]);
        $this->assertEquals('oro_sidebar_get_available_widgets', $functions[0]->getName());
        $this->assertEquals(array($this->extension, 'getWidgetDefinitions'), $functions[0]->getCallable());
    }

    public function testGetName()
    {
        $this->assertEquals(SidebarExtension::NAME, $this->extension->getName());
    }

    public function testGetWidgetDefinitions()
    {
        $placement = 'left';
        $definitions = new ArrayCollection();
        $definitions->set(
            'test',
            array(
                'title' => 'Foo',
                'icon' => 'test.ico',
                'module' => 'widget/foo',
                'placement' => 'left'
            )
        );
        $assetHelper = $this->getMockBuilder('Symfony\Component\Templating\Asset\PackageInterface')
            ->getMock();
        $assetHelper->expects($this->once())
            ->method('getUrl')
            ->with('test.ico')
            ->will($this->returnValue('/asserts/test.ico'));
        $this->container->expects($this->once())
            ->method('get')
            ->with('templating.helper.assets')
            ->will($this->returnValue($assetHelper));
        $this->widgetDefinitionsRegistry->expects($this->once())
            ->method('getWidgetDefinitionsByPlacement')
            ->with($placement)
            ->will($this->returnValue($definitions));
        $expected = array(
            'test' => array(
                'title' => 'Foo',
                'icon' => '/asserts/test.ico',
                'module' => 'widget/foo',
                'placement' => 'left'
            )
        );
        $this->assertEquals($expected, $this->extension->getWidgetDefinitions($placement));
    }
}

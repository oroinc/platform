<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;
use Oro\Bundle\SidebarBundle\Twig\SidebarExtension;

class SidebarExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WidgetDefinitionRegistry
     */
    protected $widgetDefinitionsRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

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

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new SidebarExtension(
            $this->widgetDefinitionsRegistry,
            $this->translator
        );
    }

    public function testGetFunctions()
    {
        /** @var \Twig_SimpleFunction[] $functions */
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
        $title = 'Foo';
        $definitions = new ArrayCollection();
        $definitions->set(
            'test',
            array(
                'title' => $title,
                'icon' => 'test.ico',
                'module' => 'widget/foo',
                'placement' => 'left'
            )
        );

        $this->widgetDefinitionsRegistry->expects($this->once())
            ->method('getWidgetDefinitionsByPlacement')
            ->with($placement)
            ->will($this->returnValue($definitions));

        $this->translator->expects($this->once())
            ->method('trans')
            ->with($title)
            ->will($this->returnValue('trans' . $title));

        $expected = array(
            'test' => array(
                'title' => 'transFoo',
                'icon' => 'test.ico',
                'module' => 'widget/foo',
                'placement' => 'left'
            )
        );
        $this->assertEquals($expected, $this->extension->getWidgetDefinitions($placement));
    }
}

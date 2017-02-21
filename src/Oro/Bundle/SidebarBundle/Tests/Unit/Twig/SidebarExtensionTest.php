<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Asset\Packages;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Asset\Packages as AssetHelper;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;
use Oro\Bundle\SidebarBundle\Twig\SidebarExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class SidebarExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|WidgetDefinitionRegistry */
    protected $widgetDefinitionsRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AssetHelper */
    protected $assetHelper;

    /** @var SidebarExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $featureChecker;

    protected function setUp()
    {
        $this->widgetDefinitionsRegistry = $this->getMockBuilder(WidgetDefinitionRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->assetHelper = $this->getMockBuilder(Packages::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_sidebar.widget_definition.registry', $this->widgetDefinitionsRegistry)
            ->add('translator', $this->translator)
            ->add('assets.packages', $this->assetHelper)
            ->getContainer($this);

        $this->extension = new SidebarExtension($container);
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
        $dialogIcon = 'test-icon.png';

        $definitionKey = 'test';
        $definitions->set(
            $definitionKey,
            array(
                'title' => $title,
                'icon' => 'test.ico',
                'module' => 'widget/foo',
                'placement' => 'left',
                'description' => 'Simple',
                'dialogIcon' => $dialogIcon
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

        $this->assetHelper->expects($this->once())
            ->method('getUrl')
            ->with($dialogIcon)
            ->will($this->returnValue('/' . $dialogIcon));

        $this->featureChecker
            ->method('isResourceEnabled')
            ->with($definitionKey, 'sidebar_widgets')
            ->willReturn(true);
        $this->extension->setFeatureChecker($this->featureChecker);

        $expected = array(
            'test' => array(
                'title' => 'transFoo',
                'icon' => 'test.ico',
                'module' => 'widget/foo',
                'placement' => 'left',
                'description' => 'Simple',
                'dialogIcon' => "/test-icon.png"
            )
        );
        $this->assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_sidebar_get_available_widgets', [$placement])
        );
    }
}

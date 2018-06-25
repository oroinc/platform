<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;
use Oro\Bundle\SidebarBundle\Twig\SidebarExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Translation\TranslatorInterface;

class SidebarExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WidgetDefinitionRegistry */
    protected $widgetDefinitionsRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AssetHelper */
    protected $assetHelper;

    /** @var SidebarExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
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
        $this->extension->setFeatureChecker($this->featureChecker);
    }

    public function testGetName()
    {
        self::assertEquals(SidebarExtension::NAME, $this->extension->getName());
    }

    public function testGetWidgetDefinitions()
    {
        $placement = 'left';

        $definitionKey = 'test';
        $definitions = [
            $definitionKey => [
                'title' => 'Foo',
                'icon' => 'test.ico',
                'module' => 'widget/foo',
                'placement' => 'left',
                'description' => 'Simple',
                'dialogIcon' => 'test-icon.png'
            ]
        ];

        $this->widgetDefinitionsRegistry->expects(self::once())
            ->method('getWidgetDefinitionsByPlacement')
            ->with($placement)
            ->willReturn($definitions);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($definitionKey, 'sidebar_widgets')
            ->willReturn(true);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($label) {
                return 'trans' . $label;
            });
        $this->assetHelper->expects(self::any())
            ->method('getUrl')
            ->willReturnCallback(function ($icon) {
                return '/' . $icon;
            });

        self::assertEquals(
            [
                $definitionKey => [
                    'title' => 'transFoo',
                    'icon' => '/test.ico',
                    'module' => 'widget/foo',
                    'placement' => 'left',
                    'description' => 'Simple',
                    'dialogIcon' => "/test-icon.png"
                ]
            ],
            self::callTwigFunction($this->extension, 'oro_sidebar_get_available_widgets', [$placement])
        );
    }

    public function testGetWidgetDefinitionsForDisabledWidget()
    {
        $placement = 'left';

        $definitionKey = 'test';
        $definitions = [
            $definitionKey => [
                'title' => 'Foo',
                'icon' => 'test.ico',
                'module' => 'widget/foo',
                'placement' => 'left',
                'description' => 'Simple',
                'dialogIcon' => 'test-icon.png'
            ]
        ];

        $this->widgetDefinitionsRegistry->expects(self::once())
            ->method('getWidgetDefinitionsByPlacement')
            ->with($placement)
            ->willReturn($definitions);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($definitionKey, 'sidebar_widgets')
            ->willReturn(false);
        $this->translator->expects(self::never())
            ->method('trans');
        $this->assetHelper->expects(self::never())
            ->method('getUrl');

        self::assertEquals(
            [],
            self::callTwigFunction($this->extension, 'oro_sidebar_get_available_widgets', [$placement])
        );
    }
}

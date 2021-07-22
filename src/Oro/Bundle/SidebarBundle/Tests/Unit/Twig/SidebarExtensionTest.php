<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SidebarBundle\Configuration\WidgetDefinitionProvider;
use Oro\Bundle\SidebarBundle\Twig\SidebarExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class SidebarExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WidgetDefinitionProvider */
    private $widgetDefinitionProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AssetHelper */
    private $assetHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var SidebarExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->widgetDefinitionProvider = $this->createMock(WidgetDefinitionProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->assetHelper = $this->createMock(AssetHelper::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $container = self::getContainerBuilder()
            ->add('oro_sidebar.widget_definition_provider', $this->widgetDefinitionProvider)
            ->add(TranslatorInterface::class, $this->translator)
            ->add(AssetHelper::class, $this->assetHelper)
            ->getContainer($this);

        $this->extension = new SidebarExtension($container);
        $this->extension->setFeatureChecker($this->featureChecker);
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

        $this->widgetDefinitionProvider->expects(self::once())
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
                    'dialogIcon' => '/test-icon.png'
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

        $this->widgetDefinitionProvider->expects(self::once())
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

<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SidebarBundle\Configuration\WidgetDefinitionProvider;
use Oro\Bundle\SidebarBundle\Twig\SidebarExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class SidebarExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private WidgetDefinitionProvider&MockObject $widgetDefinitionProvider;
    private TranslatorInterface&MockObject $translator;
    private AssetHelper&MockObject $assetHelper;
    private FeatureChecker&MockObject $featureChecker;
    private SidebarExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->widgetDefinitionProvider = $this->createMock(WidgetDefinitionProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->assetHelper = $this->createMock(AssetHelper::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $container = self::getContainerBuilder()
            ->add(WidgetDefinitionProvider::class, $this->widgetDefinitionProvider)
            ->add(TranslatorInterface::class, $this->translator)
            ->add(AssetHelper::class, $this->assetHelper)
            ->getContainer($this);

        $this->extension = new SidebarExtension($container);
        $this->extension->setFeatureChecker($this->featureChecker);
    }

    public function testGetWidgetDefinitions(): void
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

    public function testGetWidgetDefinitionsForDisabledWidget(): void
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

<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfTemplate\Factory;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\Factory\LayoutThemeAwarePdfTemplateFactory;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\LoaderInterface;
use Twig\Template;
use Twig\TemplateWrapper;

final class LayoutThemeAwarePdfTemplateFactoryTest extends TestCase
{
    private LayoutThemeAwarePdfTemplateFactory $factory;
    private MockObject&TwigEnvironment $twigEnvironment;
    private MockObject&ThemeConfigurationProvider $themeConfigurationProvider;
    private MockObject&ThemeManager $themeManager;

    protected function setUp(): void
    {
        $this->twigEnvironment = $this->createMock(TwigEnvironment::class);
        $this->themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->factory = new LayoutThemeAwarePdfTemplateFactory(
            $this->twigEnvironment,
            $this->themeConfigurationProvider,
            $this->themeManager
        );
    }

    public function testIsApplicableWithTemplateWrapper(): void
    {
        $templateWrapper = new TemplateWrapper(
            $this->createMock(TwigEnvironment::class),
            $this->createMock(Template::class)
        );
        self::assertFalse($this->factory->isApplicable($templateWrapper));
    }

    public function testIsApplicableWithThemePlaceholder(): void
    {
        self::assertTrue($this->factory->isApplicable('layouts/{{ themeName }}/pdf_template.html.twig'));
    }

    public function testIsApplicableWithoutThemePlaceholder(): void
    {
        self::assertFalse($this->factory->isApplicable('layouts/pdf_template.html.twig'));
    }

    public function testCreatePdfTemplateWithTemplateWrapper(): void
    {
        $templateWrapper = new TemplateWrapper(
            $this->createMock(TwigEnvironment::class),
            $this->createMock(Template::class)
        );
        $context = ['sample_key' => 'sample_value'];

        $result = $this->factory->createPdfTemplate($templateWrapper, $context);

        self::assertSame($templateWrapper, $result->getTemplate());
        self::assertSame($context, $result->getContext());
    }

    public function testCreatePdfTemplate(): void
    {
        $template = 'layouts/{{ themeName }}/pdf_template.html.twig';
        $context = ['entity' => new \stdClass()];
        $defaultTheme = 'default';

        $parentTheme = new Theme($defaultTheme);
        $currentTheme = new Theme('custom_theme', $parentTheme->getName());
        $themesHierarchy = [$parentTheme, $currentTheme];

        $twigLoader = $this->createMock(LoaderInterface::class);
        $this->twigEnvironment
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($twigLoader);

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themesHierarchy);

        $twigLoader
            ->expects(self::once())
            ->method('exists')
            ->with('layouts/' . $currentTheme->getName() . '/pdf_template.html.twig')
            ->willReturn(true);

        $result = $this->factory->createPdfTemplate($template, $context);

        self::assertSame('layouts/' . $currentTheme->getName() . '/pdf_template.html.twig', $result->getTemplate());
        self::assertSame($context, $result->getContext());
    }

    public function testCreatePdfTemplateWithFallbackToParentTheme(): void
    {
        $template = 'layouts/{{ themeName }}/pdf_template.html.twig';
        $context = ['entity' => new \stdClass()];
        $defaultTheme = 'default';

        $parentTheme = new Theme($defaultTheme);
        $currentTheme = new Theme('custom_theme', $parentTheme->getName());
        $themesHierarchy = [$parentTheme, $currentTheme];

        $twigLoader = $this->createMock(LoaderInterface::class);
        $this->twigEnvironment
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($twigLoader);

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themesHierarchy);

        $twigLoader
            ->expects(self::exactly(2))
            ->method('exists')
            ->willReturnMap([
                ['layouts/' . $currentTheme->getName() . '/pdf_template.html.twig', false],
                ['layouts/' . $parentTheme->getName() . '/pdf_template.html.twig', true],
            ]);

        $result = $this->factory->createPdfTemplate($template, $context);

        self::assertSame('layouts/' . $parentTheme->getName() . '/pdf_template.html.twig', $result->getTemplate());
        self::assertSame($context, $result->getContext());
    }

    public function testCreatePdfTemplateWithFallbackToDefaultTheme(): void
    {
        $template = 'layouts/{{ themeName }}/pdf_template.html.twig';
        $context = ['entity' => new \stdClass()];
        $defaultTheme = 'default';
        $parentTheme = new Theme('parent_theme');
        $currentTheme = new Theme('custom_theme', $parentTheme->getName());
        $themesHierarchy = [$parentTheme, $currentTheme];

        $twigLoader = $this->createMock(LoaderInterface::class);
        $this->twigEnvironment
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($twigLoader);

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themesHierarchy);

        $twigLoader
            ->expects(self::exactly(2))
            ->method('exists')
            ->willReturnMap([
                ['layouts/' . $currentTheme->getName() . '/pdf_template.html.twig', false],
                ['layouts/' . $parentTheme->getName() . '/pdf_template.html.twig', false],
            ]);

        $result = $this->factory->createPdfTemplate($template, $context);

        self::assertSame('layouts/' . $defaultTheme . '/pdf_template.html.twig', $result->getTemplate());
        self::assertSame($context, $result->getContext());
    }

    public function testCreatePdfTemplateWithNullTheme(): void
    {
        $template = 'layouts/{{ themeName }}/pdf_template.html.twig';
        $context = ['entity' => new \stdClass()];
        $themeDefault = new Theme('default');
        $themesHierarchy = [$themeDefault];

        $twigLoader = $this->createMock(LoaderInterface::class);
        $this->twigEnvironment
            ->expects(self::once())
            ->method('getLoader')
            ->willReturn($twigLoader);

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn(null);

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($themeDefault->getName())
            ->willReturn($themesHierarchy);

        $twigLoader
            ->expects(self::once())
            ->method('exists')
            ->with('layouts/' . $themeDefault->getName() . '/pdf_template.html.twig')
            ->willReturn(true);

        $result = $this->factory->createPdfTemplate($template, $context);

        self::assertSame('layouts/' . $themeDefault->getName() . '/pdf_template.html.twig', $result->getTemplate());
        self::assertSame($context, $result->getContext());
    }
}

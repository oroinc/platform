<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\PdfTemplate;

use Oro\Bundle\PdfGeneratorBundle\Layout\Extension\PdfDocumentTemplatesThemeConfigurationExtension;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\PdfTemplate\LayoutThemeAwarePdfDocumentTemplateProvider;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class LayoutThemeAwarePdfDocumentTemplateProviderTest extends TestCase
{
    private LayoutThemeAwarePdfDocumentTemplateProvider $provider;

    private ThemeConfigurationProvider&MockObject $themeConfigurationProvider;

    private ThemeManager&MockObject $themeManager;

    protected function setUp(): void
    {
        $this->themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->provider = new LayoutThemeAwarePdfDocumentTemplateProvider(
            $this->themeConfigurationProvider,
            $this->themeManager
        );
    }

    public function testGetContentTemplateReturnsFromCurrentTheme(): void
    {
        $pdfDocumentType = 'sample';
        $expectedTemplatePath = '@OroPdfGenerator/PdfDocument/content.html.twig';
        $parentTheme = new Theme('parent_theme');
        $currentTheme = new Theme('current_theme', $parentTheme->getName());
        $currentTheme->setConfig([
            PdfDocumentTemplatesThemeConfigurationExtension::PDF_DOCUMENT => [
                $pdfDocumentType => [
                    PdfDocumentTemplatesThemeConfigurationExtension::CONTENT_TEMPLATE => $expectedTemplatePath,
                ],
            ],
        ]);
        $themeHierarchy = [$parentTheme, $currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themeHierarchy);

        $result = $this->provider->getContentTemplate($pdfDocumentType);

        self::assertSame($expectedTemplatePath, $result);
    }

    public function testGetHeaderTemplateReturnsFromCurrentTheme(): void
    {
        $pdfDocumentType = 'sample';
        $expectedTemplatePath = '@OroPdfGenerator/PdfDocument/header.html.twig';
        $parentTheme = new Theme('parent_theme');
        $currentTheme = new Theme('current_theme', $parentTheme->getName());
        $currentTheme->setConfig([
            PdfDocumentTemplatesThemeConfigurationExtension::PDF_DOCUMENT => [
                $pdfDocumentType => [
                    PdfDocumentTemplatesThemeConfigurationExtension::HEADER_TEMPLATE => $expectedTemplatePath,
                ],
            ],
        ]);
        $themeHierarchy = [$parentTheme, $currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themeHierarchy);

        $result = $this->provider->getHeaderTemplate($pdfDocumentType);

        self::assertSame($expectedTemplatePath, $result);
    }

    public function testGetFooterTemplateReturnsFromCurrentTheme(): void
    {
        $pdfDocumentType = 'sample';
        $expectedTemplatePath = '@OroPdfGenerator/PdfDocument/footer.html.twig';
        $parentTheme = new Theme('parent_theme');
        $currentTheme = new Theme('current_theme', $parentTheme->getName());
        $currentTheme->setConfig([
            PdfDocumentTemplatesThemeConfigurationExtension::PDF_DOCUMENT => [
                $pdfDocumentType => [
                    PdfDocumentTemplatesThemeConfigurationExtension::FOOTER_TEMPLATE => $expectedTemplatePath,
                ],
            ],
        ]);
        $themeHierarchy = [$parentTheme, $currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themeHierarchy);

        $result = $this->provider->getFooterTemplate($pdfDocumentType);

        self::assertSame($expectedTemplatePath, $result);
    }

    public function testGetContentTemplateReturnsFromParentThemeConfig(): void
    {
        $pdfDocumentType = 'sample';
        $expectedTemplatePath = '@OroPdfGenerator/PdfDocument/content.html.twig';
        $parentTheme = new Theme('parent_theme');
        $parentTheme->setConfig([
            PdfDocumentTemplatesThemeConfigurationExtension::PDF_DOCUMENT => [
                $pdfDocumentType => [
                    PdfDocumentTemplatesThemeConfigurationExtension::CONTENT_TEMPLATE => $expectedTemplatePath,
                ],
            ],
        ]);
        $currentTheme = new Theme('current_theme', $parentTheme->getName());
        $themeHierarchy = [$parentTheme, $currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themeHierarchy);

        $result = $this->provider->getContentTemplate($pdfDocumentType);

        self::assertSame($expectedTemplatePath, $result);
    }

    public function testGetHeaderTemplateReturnsFromParentThemeConfig(): void
    {
        $pdfDocumentType = 'sample';
        $expectedTemplatePath = '@OroPdfGenerator/PdfDocument/header.html.twig';
        $parentTheme = new Theme('parent_theme');
        $parentTheme->setConfig([
            PdfDocumentTemplatesThemeConfigurationExtension::PDF_DOCUMENT => [
                $pdfDocumentType => [
                    PdfDocumentTemplatesThemeConfigurationExtension::HEADER_TEMPLATE => $expectedTemplatePath,
                ],
            ],
        ]);
        $currentTheme = new Theme('current_theme', $parentTheme->getName());
        $themeHierarchy = [$parentTheme, $currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themeHierarchy);

        $result = $this->provider->getHeaderTemplate($pdfDocumentType);

        self::assertSame($expectedTemplatePath, $result);
    }

    public function testGetFooterTemplateReturnsFromParentThemeConfig(): void
    {
        $pdfDocumentType = 'sample';
        $expectedTemplatePath = '@OroPdfGenerator/PdfDocument/footer.html.twig';
        $parentTheme = new Theme('parent_theme');
        $parentTheme->setConfig([
            PdfDocumentTemplatesThemeConfigurationExtension::PDF_DOCUMENT => [
                $pdfDocumentType => [
                    PdfDocumentTemplatesThemeConfigurationExtension::FOOTER_TEMPLATE => $expectedTemplatePath,
                ],
            ],
        ]);
        $currentTheme = new Theme('current_theme', $parentTheme->getName());
        $themeHierarchy = [$parentTheme, $currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themeHierarchy);

        $result = $this->provider->getFooterTemplate($pdfDocumentType);

        self::assertSame($expectedTemplatePath, $result);
    }

    public function testGetContentTemplateReturnsNullWhenNoTemplateFound(): void
    {
        $pdfDocumentType = 'non_existing_type';
        $parentTheme = new Theme('parent_theme');
        $currentTheme = new Theme('current_theme', $parentTheme->getName());
        $themeHierarchy = [$parentTheme, $currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themeHierarchy);

        $result = $this->provider->getContentTemplate($pdfDocumentType);

        self::assertNull($result);
    }

    public function testGetHeaderTemplateReturnsNullWhenNoTemplateFound(): void
    {
        $pdfDocumentType = 'non_existing_type';
        $parentTheme = new Theme('parent_theme');
        $currentTheme = new Theme('current_theme', $parentTheme->getName());
        $themeHierarchy = [$parentTheme, $currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themeHierarchy);

        $result = $this->provider->getHeaderTemplate($pdfDocumentType);

        self::assertNull($result);
    }

    public function testGetFooterTemplateReturnsNullWhenNoTemplateFound(): void
    {
        $pdfDocumentType = 'non_existing_type';
        $parentTheme = new Theme('parent_theme');
        $currentTheme = new Theme('current_theme', $parentTheme->getName());
        $themeHierarchy = [$parentTheme, $currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn($currentTheme->getName());

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with($currentTheme->getName())
            ->willReturn($themeHierarchy);

        $result = $this->provider->getFooterTemplate($pdfDocumentType);

        self::assertNull($result);
    }

    public function testGetContentTemplateReturnsNullWhenThemeHierarchyIsEmpty(): void
    {
        $pdfDocumentType = 'sample';
        $currentTheme = new Theme('current_theme');
        $themeHierarchy = [$currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn('current_theme');

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with('current_theme')
            ->willReturn($themeHierarchy);

        $result = $this->provider->getContentTemplate($pdfDocumentType);

        self::assertNull($result);
    }

    public function testGetHeaderTemplateReturnsNullWhenThemeHierarchyIsEmpty(): void
    {
        $pdfDocumentType = 'sample';
        $currentTheme = new Theme('current_theme');
        $themeHierarchy = [$currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn('current_theme');

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with('current_theme')
            ->willReturn($themeHierarchy);

        $result = $this->provider->getHeaderTemplate($pdfDocumentType);

        self::assertNull($result);
    }

    public function testGetFooterTemplateReturnsNullWhenThemeHierarchyIsEmpty(): void
    {
        $pdfDocumentType = 'sample';
        $currentTheme = new Theme('current_theme');
        $themeHierarchy = [$currentTheme];

        $this->themeConfigurationProvider
            ->expects(self::once())
            ->method('getThemeName')
            ->willReturn('current_theme');

        $this->themeManager
            ->expects(self::once())
            ->method('getThemesHierarchy')
            ->with('current_theme')
            ->willReturn($themeHierarchy);

        $result = $this->provider->getFooterTemplate($pdfDocumentType);

        self::assertNull($result);
    }
}

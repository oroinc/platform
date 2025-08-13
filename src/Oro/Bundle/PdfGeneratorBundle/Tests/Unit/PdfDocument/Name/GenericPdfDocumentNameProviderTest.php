<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Name;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Name\GenericPdfDocumentNameProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GenericPdfDocumentNameProviderTest extends TestCase
{
    private GenericPdfDocumentNameProvider $nameProvider;

    private MockObject&EntityNameResolver $entityNameResolver;

    private MockObject&PreferredLocalizationProviderInterface $preferredLocalizationProvider;

    protected function setUp(): void
    {
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->preferredLocalizationProvider = $this->createMock(PreferredLocalizationProviderInterface::class);

        $this->nameProvider = new GenericPdfDocumentNameProvider(
            $this->entityNameResolver,
            $this->preferredLocalizationProvider
        );
    }

    public function testCreatePdfDocumentNameWithSimpleEntityAndNoFormatOrLocale(): void
    {
        $sourceEntity = new \stdClass();
        $preferredLocalization = new Localization();
        $expectedName = 'simple-entity-name';

        $this->preferredLocalizationProvider
            ->expects(self::once())
            ->method('getPreferredLocalization')
            ->with($sourceEntity)
            ->willReturn($preferredLocalization);

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, null, $preferredLocalization)
            ->willReturn('Simple Entity Name');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity);

        self::assertSame($expectedName, $result);
    }

    public function testCreatePdfDocumentNameWithFormatSpecified(): void
    {
        $sourceEntity = new \stdClass();
        $format = 'short';
        $preferredLocalization = new Localization();
        $expectedName = 'short-name';

        $this->preferredLocalizationProvider
            ->expects(self::once())
            ->method('getPreferredLocalization')
            ->with($sourceEntity)
            ->willReturn($preferredLocalization);

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, $format, $preferredLocalization)
            ->willReturn('Short Name');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity, $format);

        self::assertSame($expectedName, $result);
    }

    public function testCreatePdfDocumentNameWithStringLocale(): void
    {
        $sourceEntity = new \stdClass();
        $locale = 'en_US';
        $expectedName = 'entity-name-en_us';

        $this->preferredLocalizationProvider
            ->expects(self::never())
            ->method('getPreferredLocalization');

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, null, $locale)
            ->willReturn('Entity Name EN_US');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity, null, $locale);

        self::assertSame($expectedName, $result);
    }

    public function testCreatePdfDocumentNameWithLocalizationObject(): void
    {
        $sourceEntity = new \stdClass();
        $localization = new Localization();
        $expectedName = 'localized-entity-name';

        $this->preferredLocalizationProvider
            ->expects(self::never())
            ->method('getPreferredLocalization');

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, null, $localization)
            ->willReturn('Localized Entity Name');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity, null, $localization);

        self::assertSame($expectedName, $result);
    }

    public function testCreatePdfDocumentNameSanitizesSpecialCharacters(): void
    {
        $sourceEntity = new \stdClass();
        $preferredLocalization = null;
        $expectedName = 'special-name';

        $this->preferredLocalizationProvider
            ->expects(self::once())
            ->method('getPreferredLocalization')
            ->with($sourceEntity)
            ->willReturn($preferredLocalization);

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, null, $preferredLocalization)
            ->willReturn('Special@Name!');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity);

        self::assertSame($expectedName, $result);
    }

    public function testCreatePdfDocumentNameConvertsToLowercase(): void
    {
        $sourceEntity = new \stdClass();
        $preferredLocalization = null;
        $expectedName = 'lowercase-name';

        $this->preferredLocalizationProvider
            ->expects(self::once())
            ->method('getPreferredLocalization')
            ->with($sourceEntity)
            ->willReturn($preferredLocalization);

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, null, $preferredLocalization)
            ->willReturn('LowerCase-Name');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity);

        self::assertSame($expectedName, $result);
    }

    public function testCreatePdfDocumentNameWithPreferredLocalization(): void
    {
        $sourceEntity = new \stdClass();
        $preferredLocalization = new Localization();
        $expectedName = 'invoice-123';

        $this->preferredLocalizationProvider
            ->expects(self::once())
            ->method('getPreferredLocalization')
            ->with($sourceEntity)
            ->willReturn($preferredLocalization);

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, null, $preferredLocalization)
            ->willReturn('Invoice #123');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity);

        self::assertSame($expectedName, $result);
    }

    public function testCreatePdfDocumentNameWithComplexSanitization(): void
    {
        $sourceEntity = new \stdClass();
        $preferredLocalization = null;
        $expectedName = 'invoice-123-customer-name-2024-01-15.pdf';

        $this->preferredLocalizationProvider
            ->expects(self::once())
            ->method('getPreferredLocalization')
            ->with($sourceEntity)
            ->willReturn($preferredLocalization);

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, null, $preferredLocalization)
            ->willReturn('Invoice #123 - Customer Name (2024/01/15).pdf');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity);

        self::assertSame($expectedName, $result);
    }
}

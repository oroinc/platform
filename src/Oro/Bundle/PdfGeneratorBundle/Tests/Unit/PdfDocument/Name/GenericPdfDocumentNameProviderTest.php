<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfDocument\Name;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\Name\GenericPdfDocumentNameProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GenericPdfDocumentNameProviderTest extends TestCase
{
    private GenericPdfDocumentNameProvider $nameProvider;

    private MockObject&EntityNameResolver $entityNameResolver;

    protected function setUp(): void
    {
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->nameProvider = new GenericPdfDocumentNameProvider($this->entityNameResolver);
    }

    public function testCreatePdfDocumentNameWithSimpleEntityAndNoFormatOrLocale(): void
    {
        $sourceEntity = new \stdClass();
        $expectedName = 'simple-entity-name';

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, null, null)
            ->willReturn('Simple Entity Name');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity);

        self::assertSame($expectedName, $result);
    }

    public function testCreatePdfDocumentNameWithFormatSpecified(): void
    {
        $sourceEntity = new \stdClass();
        $format = 'short';
        $expectedName = 'short-name';

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, $format, null)
            ->willReturn('Short Name');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity, $format);

        self::assertSame($expectedName, $result);
    }

    public function testCreatePdfDocumentNameWithStringLocale(): void
    {
        $sourceEntity = new \stdClass();
        $locale = 'en_US';
        $expectedName = 'entity-name-en_us';

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
        $expectedName = 'special-name';

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, null, null)
            ->willReturn('Special@Name!');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity);

        self::assertSame($expectedName, $result);
    }

    public function testCreatePdfDocumentNameConvertsToLowercase(): void
    {
        $sourceEntity = new \stdClass();
        $expectedName = 'lowercase-name';

        $this->entityNameResolver
            ->expects(self::once())
            ->method('getName')
            ->with($sourceEntity, null, null)
            ->willReturn('LowerCase-Name');

        $result = $this->nameProvider->createPdfDocumentName($sourceEntity);

        self::assertSame($expectedName, $result);
    }
}

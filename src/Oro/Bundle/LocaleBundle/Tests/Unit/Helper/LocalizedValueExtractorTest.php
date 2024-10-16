<?php

declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizedValueExtractor;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use PHPUnit\Framework\TestCase;

class LocalizedValueExtractorTest extends TestCase
{
    private LocalizedValueExtractor $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->helper = new LocalizedValueExtractor();
    }

    public function testEmptyValues(): void
    {
        $localization = $this->createMock(Localization::class);
        $localization->expects(self::never())->method(self::anything());

        $values = [];

        self::assertNull($this->helper->getLocalizedFallbackValue($values, $localization));
    }

    public function testDefaultValueWithoutLocalization(): void
    {
        $values = [
            "" => 'test',
            1 => new FallbackType('system'),
            2 => new FallbackType('parent_localization'),
            3 => 'overridden',
        ];

        self::assertEquals('test', $this->helper->getLocalizedFallbackValue($values, null));
    }

    public function testValueForLocalization(): void
    {
        $localization = $this->createMock(Localization::class);
        $localization->expects(self::once())->method('getId')->willReturn(3);

        $values = [
            "" => 'test',
            1 => new FallbackType('system'),
            2 => new FallbackType('parent_localization'),
            3 => 'overridden',
        ];

        self::assertEquals('overridden', $this->helper->getLocalizedFallbackValue($values, $localization));
    }

    public function testValueFallbackToSystemForLocalization(): void
    {
        $localization = $this->createMock(Localization::class);
        $localization->expects(self::once())->method('getId')->willReturn(1);

        $values = [
            "" => 'test',
            1 => new FallbackType('system'),
            2 => new FallbackType('parent_localization'),
            3 => 'overridden',
        ];

        self::assertEquals('test', $this->helper->getLocalizedFallbackValue($values, $localization));
    }

    public function testValueFallbackToParentForLocalization(): void
    {
        $parentLocalization = $this->createMock(Localization::class);
        $parentLocalization->expects(self::once())->method('getId')->willReturn(3);

        $localization = $this->createMock(Localization::class);
        $localization->expects(self::once())->method('getId')->willReturn(2);
        $localization->expects(self::once())->method('getParentLocalization')->willReturn($parentLocalization);

        $values = [
            "" => 'test',
            1 => new FallbackType('system'),
            2 => new FallbackType('parent_localization'),
            3 => 'overridden',
        ];

        self::assertEquals('overridden', $this->helper->getLocalizedFallbackValue($values, $localization));
    }

    public function testValueFallbackToDefaultForLocalization(): void
    {
        $localization = $this->createMock(Localization::class);
        $localization->expects(self::once())->method('getId')->willReturn(5);

        $values = [
            "" => 'test',
            1 => new FallbackType('system'),
            2 => new FallbackType('parent_localization'),
            3 => 'overridden',
        ];

        self::assertEquals('test', $this->helper->getLocalizedFallbackValue($values, $localization));
    }
}

<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Api;

use Oro\Bundle\LocaleBundle\Api\LocalizedFallbackValueExtractor;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class LocalizedFallbackValueExtractorTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizedFallbackValueExtractor */
    private $valueExtractor;

    protected function setUp(): void
    {
        $this->valueExtractor = new LocalizedFallbackValueExtractor();
    }

    public function testExtractValueForEmptyLocalizedFallbackValue(): void
    {
        $value = new LocalizedFallbackValue();

        self::assertNull($this->valueExtractor->extractValue($value));
    }

    public function testExtractValueForLocalizedFallbackValueWithStringValue(): void
    {
        $value = new LocalizedFallbackValue();
        $value->setString('test');

        self::assertEquals('test', $this->valueExtractor->extractValue($value));
    }

    public function testExtractValueForLocalizedFallbackValueWithTextValue(): void
    {
        $value = new LocalizedFallbackValue();
        $value->setText('test');

        self::assertEquals('test', $this->valueExtractor->extractValue($value));
    }
}

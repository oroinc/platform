<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\ImportExport\Serializer\Normalizer;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\ImportExport\Serializer\Normalizer\MultiCurrencyNormalizer;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

class MultiCurrencyNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var MultiCurrencyNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(NumberFormatter::class);
        $this->normalizer = new MultiCurrencyNormalizer($this->formatter);
    }

    public function testSupportsNormalization()
    {
        $multiCurrency = new MultiCurrency();
        $isNormalizationSupports = $this->normalizer->supportsNormalization($multiCurrency);
        $this->assertTrue($isNormalizationSupports);
    }

    public function testNotSupportsNormalization()
    {
        $multiCurrency = new \stdClass();
        $isNormalizationSupports = $this->normalizer->supportsNormalization($multiCurrency);
        $this->assertFalse($isNormalizationSupports);
    }

    /**
     * @dataProvider getNormalizerData
     */
    public function testNormalizeShouldGenerateCorrectString(string $value, string $currency)
    {
        $multiCurrency = new MultiCurrency();
        $formattedValue = $currency . $value;
        $this->formatter->expects($this->once())
            ->method('formatCurrency')
            ->willReturn($formattedValue);

        $this->assertEquals($formattedValue, $this->normalizer->normalize($multiCurrency));
    }

    public function getNormalizerData(): array
    {
        return [
            ['100.00', 'USD']
        ];
    }
}

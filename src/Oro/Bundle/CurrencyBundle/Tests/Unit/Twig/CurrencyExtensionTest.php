<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Twig;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeConfigProvider;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Utils\CurrencyNameHelperStub;
use Oro\Bundle\CurrencyBundle\Twig\CurrencyExtension;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class CurrencyExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|NumberFormatter */
    private $formatter;

    /** @var CurrencyExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(NumberFormatter::class);
        $viewTypeProvider = $this->createMock(ViewTypeConfigProvider::class);
        $currencyNameHelper = new CurrencyNameHelperStub();

        $container = self::getContainerBuilder()
            ->add('oro_locale.formatter.number', $this->formatter)
            ->add('oro_currency.provider.view_type', $viewTypeProvider)
            ->add('oro_currency.helper.currency_name', $currencyNameHelper)
            ->getContainer($this);

        $this->extension = new CurrencyExtension($container);
    }

    /**
     * @dataProvider formatCurrencyDataProvider
     */
    public function testFormatCurrency(Price $price, array $options, string $expected)
    {
        $this->formatter->expects($this->once())
            ->method('formatCurrency')
            ->with(
                $price->getValue(),
                $price->getCurrency(),
                $options['attributes'],
                $options['textAttributes'],
                $options['symbols'],
                $options['locale']
            )
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_price', [$price, $options])
        );
    }

    public function formatCurrencyDataProvider(): array
    {
        return [
            '$1,234.5' => [
                'price' => new Price(),
                'options' => [
                    'attributes' => ['grouping_size' => 3],
                    'textAttributes' => ['grouping_separator_symbol' => ','],
                    'symbols' => ['symbols' => '$'],
                    'locale' => 'en_US'
                ],
                'expected' => '$1,234.5'
            ]
        ];
    }

    public function testGetSymbolCollection()
    {
        $this->assertEquals(
            ['USD' => ['symbol' => '$'], 'EUR' => ['symbol' => '€']],
            self::callTwigFunction($this->extension, 'oro_currency_symbol_collection', [])
        );
    }
}

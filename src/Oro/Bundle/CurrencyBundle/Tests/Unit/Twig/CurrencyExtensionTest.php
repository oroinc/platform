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

    /** @var CurrencyExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|NumberFormatter */
    protected $formatter;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder(NumberFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewTypeProvider = $this->getMockBuilder(ViewTypeConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currencyNameHelper = new CurrencyNameHelperStub();

        $container = self::getContainerBuilder()
            ->add('oro_locale.formatter.number', $this->formatter)
            ->add('oro_currency.provider.view_type', $viewTypeProvider)
            ->add('oro_currency.helper.currency_name', $currencyNameHelper)
            ->getContainer($this);

        $this->extension = new CurrencyExtension($container);
    }

    /**
     * @param Price $price
     * @param array $options
     * @param string $expected
     * @dataProvider formatCurrencyDataProvider
     */
    public function testFormatCurrency(Price $price, array $options, $expected)
    {
        $this->formatter->expects($this->once())->method('formatCurrency')
            ->with(
                $price->getValue(),
                $price->getCurrency(),
                $options['attributes'],
                $options['textAttributes'],
                $options['symbols'],
                $options['locale']
            )
            ->will($this->returnValue($expected));

        $this->assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_price', [$price, $options])
        );
    }

    /**
     * @return array
     */
    public function formatCurrencyDataProvider()
    {
        return [
            '$1,234.5' => [
                'price' => new Price(1234.5, 'USD'),
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

    public function testGetName()
    {
        $this->assertEquals('oro_currency', $this->extension->getName());
    }

    public function testGetSymbolCollection()
    {
        $this->assertEquals(
            ['USD' => ['symbol' => '$'], 'EUR' => ['symbol' => '€']],
            self::callTwigFunction($this->extension, 'oro_currency_symbol_collection', [])
        );
    }
}

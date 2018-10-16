<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Twig\NumberExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class NumberExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var NumberExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $formatter;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder(NumberFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_locale.formatter.number', $this->formatter)
            ->getContainer($this);

        $this->extension = new NumberExtension($container);
    }

    public function testGetAttribute()
    {
        $attribute = 'grouping_used';
        $style = 'decimal';
        $locale = 'fr_CA';
        $attributes = ['decimal_digits' => 4];
        $expectedResult = 1;

        $this->formatter->expects($this->once())->method('getAttribute')
            ->with($attribute, $style, $locale, $attributes)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFunction(
                $this->extension,
                'oro_locale_number_attribute',
                [$attribute, $style, $locale, $attributes]
            )
        );
    }

    public function testGetTextAttribute()
    {
        $attribute = 'currency_code';
        $style = 'decimal';
        $locale = 'en_US';
        $expectedResult = '$';

        $this->formatter->expects($this->once())->method('getTextAttribute')
            ->with($attribute, $style, $locale)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFunction(
                $this->extension,
                'oro_locale_number_text_attribute',
                [$attribute, $style, $locale]
            )
        );
    }

    public function testGetSymbol()
    {
        $symbol = 'percent_symbol';
        $style = 'decimal';
        $locale = 'fr_CA';
        $expectedResult = '%';

        $this->formatter->expects($this->once())->method('getSymbol')
            ->with($symbol, $style, $locale)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_locale_number_symbol', [$symbol, $style, $locale])
        );
    }

    public function testFormat()
    {
        $value = 1234.5;
        $style = 'decimal';
        $attributes = array('grouping_size' => 3);
        $textAttributes = array('grouping_separator_symbol' => ',');
        $symbols = array('symbols' => '$');
        $locale = 'fr_CA';
        $options = array(
            'attributes' => $attributes, 'textAttributes' => $textAttributes, 'symbols' => $symbols, 'locale' => $locale
        );
        $expectedResult = '1,234.45';

        $this->formatter->expects($this->once())->method('format')
            ->with($value, $style, $attributes, $textAttributes, $symbols, $locale)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_number', [$value, $style, $options])
        );
    }

    public function testFormatCurrency()
    {
        $value = 1234.5;
        $currency = 'USD';
        $attributes = array('grouping_size' => 3);
        $textAttributes = array('grouping_separator_symbol' => ',');
        $symbols = array('symbols' => '$');
        $locale = 'en_US';
        $options = array(
            'currency' => $currency,
            'attributes' => $attributes,
            'textAttributes' => $textAttributes,
            'symbols' => $symbols,
            'locale' => $locale
        );
        $expectedResult = '$1,234.45';

        $this->formatter->expects($this->once())->method('formatCurrency')
            ->with($value, $currency, $attributes, $textAttributes, $symbols, $locale)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_currency', [$value, $options])
        );
    }

    public function testFormatDecimal()
    {
        $value = 1234.5;
        $attributes = array('grouping_size' => 3);
        $textAttributes = array('grouping_separator_symbol' => ',');
        $symbols = array('symbols' => '$');
        $locale = 'en_US';
        $options = array(
            'attributes' => $attributes,
            'textAttributes' => $textAttributes,
            'symbols' => $symbols,
            'locale' => $locale
        );
        $expectedResult = '1,234.45';

        $this->formatter->expects($this->once())->method('formatDecimal')
            ->with($value, $attributes, $textAttributes, $symbols, $locale)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_decimal', [$value, $options])
        );
    }

    public function testFormatPercent()
    {
        $value = 99;
        $attributes = array('grouping_size' => 3);
        $textAttributes = array('grouping_separator_symbol' => ',');
        $symbols = array('symbols' => '$');
        $locale = 'en_US';
        $options = array(
            'attributes' => $attributes,
            'textAttributes' => $textAttributes,
            'symbols' => $symbols,
            'locale' => $locale
        );
        $expectedResult = '99%';

        $this->formatter->expects($this->once())->method('formatPercent')
            ->with($value, $attributes, $textAttributes, $symbols, $locale)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_percent', [$value, $options])
        );
    }

    public function testFormatSpellout()
    {
        $value = 1;
        $attributes = array('foo' => 1);
        $textAttributes = array('bar' => 'baz');
        $symbols = array('symbols' => '$');
        $locale = 'en_US';
        $options = array(
            'attributes' => $attributes,
            'textAttributes' => $textAttributes,
            'symbols' => $symbols,
            'locale' => $locale
        );
        $expectedResult = 'one';

        $this->formatter->expects($this->once())->method('formatSpellout')
            ->with($value, $attributes, $textAttributes, $symbols, $locale)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_spellout', [$value, $options])
        );
    }

    public function testFormatDuration()
    {
        $value = 1;
        $attributes = array('foo' => 1);
        $textAttributes = array('bar' => 'baz');
        $symbols = array('symbols' => '$');
        $locale = 'en_US';
        $options = array(
            'attributes' => $attributes,
            'textAttributes' => $textAttributes,
            'symbols' => $symbols,
            'locale' => $locale
        );
        $expectedResult = '1 sec';

        $this->formatter->expects($this->once())->method('formatDuration')
            ->with($value, $attributes, $textAttributes, $symbols, $locale)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_duration', [$value, $options])
        );
    }

    public function testFormatOrdinal()
    {
        $value = 1;
        $attributes = array('foo' => 1);
        $textAttributes = array('bar' => 'baz');
        $symbols = array('symbols' => '$');
        $locale = 'en_US';
        $options = array(
            'attributes' => $attributes,
            'textAttributes' => $textAttributes,
            'symbols' => $symbols,
            'locale' => $locale
        );
        $expectedResult = '1st';

        $this->formatter->expects($this->once())->method('formatOrdinal')
            ->with($value, $attributes, $textAttributes, $symbols, $locale)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_ordinal', [$value, $options])
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale_number', $this->extension->getName());
    }
}

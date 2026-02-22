<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Twig;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Utils\CurrencyNameHelperStub;
use Oro\Bundle\CurrencyBundle\Twig\CurrencyExtension;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrencyExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private ViewTypeProviderInterface&MockObject $viewTypeProvider;
    private NumberFormatter&MockObject $formatter;
    private CurrencyExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->viewTypeProvider = $this->createMock(ViewTypeProviderInterface::class);
        $this->formatter = $this->createMock(NumberFormatter::class);

        $container = self::getContainerBuilder()
            ->add('oro_currency.provider.view_type', $this->viewTypeProvider)
            ->add(CurrencyNameHelper::class, new CurrencyNameHelperStub())
            ->add(NumberFormatter::class, $this->formatter)
            ->getContainer($this);

        $this->extension = new CurrencyExtension($container);
    }

    public function testGetViewType(): void
    {
        $viewType = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;

        $this->viewTypeProvider->expects(self::once())
            ->method('getViewType')
            ->willReturn($viewType);

        self::assertEquals(
            $viewType,
            self::callTwigFunction($this->extension, 'oro_currency_view_type', [])
        );
    }

    public function testGetSymbolCollection(): void
    {
        self::assertEquals(
            ['USD' => ['symbol' => '$'], 'EUR' => ['symbol' => '€']],
            self::callTwigFunction($this->extension, 'oro_currency_symbol_collection', [])
        );
    }

    /**
     * @dataProvider formatCurrencyDataProvider
     */
    public function testFormatCurrency(Price $price, array $options, string $expected): void
    {
        $this->formatter->expects(self::once())
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

        self::assertEquals(
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
}

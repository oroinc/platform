<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroMoneyType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroMoneyTypeTest extends FormIntegrationTestCase
{
    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var OroMoneyType */
    private $formType;

    protected function setUp(): void
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        $this->formType = new OroMoneyType($this->localeSettings, $this->numberFormatter);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(OroMoneyType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(MoneyType::class, $this->formType->getParent());
    }

    public function bindDataProvider(): array
    {
        return [
            'default en locale' => [
                'locale'         => 'en',
                'currency'       => 'USD',
                'currencySymbol' => '$',
                'symbolPrepend'  => true,
                'data'           => 11.22,
                'viewData'       => [
                    'money_pattern' => '{{ currency }}{{ widget }}',
                    'currency_symbol' => '$',
                    'currency_symbol_prepend' => true
                ],
            ],
            'default ru locale' => [
                'locale'         => 'ru',
                'currency'       => 'RUR',
                'currencySymbol' => 'руб.',
                'symbolPrepend'  => false,
                'data'           => 11.22,
                'viewData'       => [
                    'money_pattern' => '{{ widget }}{{ currency }}',
                    'currency_symbol' => 'руб.',
                    'currency_symbol_prepend' => false
                ],
            ],
            'custom currency' => [
                'locale'         => 'en',
                'currency'       => 'EUR',
                'currencySymbol' => '€',
                'symbolPrepend'  => true,
                'data'           => 11.22,
                'viewData'       => [
                    'money_pattern' => '{{ currency }}{{ widget }}',
                    'currency_symbol' => '€',
                    'currency_symbol_prepend' => true
                ],
            ],
        ];
    }

    /**
     * @dataProvider bindDataProvider
     */
    public function testBindData(
        string $locale,
        string $currency,
        string $currencySymbol,
        bool $symbolPrepend,
        float $data,
        array $viewData,
        array $options = []
    ) {
        $this->localeSettings->expects($this->any())
            ->method('getLocale')
            ->willReturn($locale);
        $this->localeSettings->expects($this->any())
            ->method('getCurrency')
            ->willReturn($currency);
        $this->localeSettings->expects($this->any())
            ->method('getCurrencySymbolByCurrency')
            ->with($currency)
            ->willReturn($currencySymbol);

        $this->numberFormatter->expects($this->any())
            ->method('isCurrencySymbolPrepend')
            ->with($currency)
            ->willReturn($symbolPrepend);

        $this->numberFormatter->expects($this->any())
            ->method('getAttribute')
            ->with(\NumberFormatter::GROUPING_USED)
            ->willReturn(1);

        $form = $this->factory->create(OroMoneyType::class, null, $options);

        $form->submit($data);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($data, $form->getData());

        $view = $form->createView();

        foreach ($viewData as $key => $value) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($value, $view->vars[$key]);
        }
    }
}

<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Utils\CurrencyNameHelperStub;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Currencies;

class CurrencySelectionTypeTest extends FormIntegrationTestCase
{
    /** @var CurrencyProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyProvider;

    /** @var CurrencySelectionType */
    private $formType;

    protected function setUp(): void
    {
        $this->currencyProvider = $this->createMock(CurrencyProviderInterface::class);

        $localeSettings = $this->createMock(LocaleSettings::class);
        $localeSettings->expects($this->any())
            ->method('getLocale')
            ->willReturn(\Locale::getDefault());

        $this->formType = new CurrencySelectionType(
            $this->currencyProvider,
            $localeSettings,
            new CurrencyNameHelperStub()
        );

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

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $allowedCurrencies,
        array $inputOptions,
        array $expectedOptions,
        string $submittedData
    ) {
        $this->currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn($allowedCurrencies);

        $form = $this->factory->create(CurrencySelectionType::class, null, $inputOptions);
        $formConfig = $form->getConfig();

        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
        }

        if (!isset($inputOptions['full_currency_list']) || !$inputOptions['full_currency_list']) {
            $this->assertEquals($expectedOptions['choices'], $form->createView()->vars['choices']);
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider(): array
    {
        $usdSymbol = Currencies::getSymbol('USD');
        $gbpSymbol = Currencies::getSymbol('GBP');
        $rubSymbol = Currencies::getSymbol('RUB');
        $uahSymbol = Currencies::getSymbol('UAH');

        $usdName = Currencies::getName('USD');
        $uahName = Currencies::getName('UAH');

        return [
            'currency symbol and data from system config' => [
                'allowedCurrencies' => ['UAH', 'USD'],
                'inputOptions' => [],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [new ChoiceView('UAH', 'UAH', $uahSymbol), new ChoiceView('USD', 'USD', $usdSymbol)]
                ],
                'submittedData' => 'UAH'
            ],
            'compact currency name and data from system config' => [
                'allowedCurrencies' => ['USD', 'UAH'],
                'inputOptions' => [
                    'compact' => true
                ],
                'expectedOptions' => [
                    'compact' => true,
                    'choices' => [ new ChoiceView('USD', 'USD', 'USD'), new ChoiceView('UAH', 'UAH', 'UAH')]
                ],
                'submittedData' => 'UAH'
            ],
            'currency symbol and data from currencies_list option' => [
                'allowedCurrencies' => ['USD', 'UAH'],
                'inputOptions' => [
                    'compact' => false,
                    'currencies_list' => ['RUB']
                ],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [ new ChoiceView('RUB', 'RUB', $rubSymbol) ]
                ],
                'submittedData' => 'RUB'
            ],
            'currency symbol, data from system config and additional currencies' => [
                'allowedCurrencies' => ['UAH', 'USD'],
                'inputOptions' => [
                    'additional_currencies' => ['GBP']
                ],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [
                        new ChoiceView('UAH', 'UAH', $uahSymbol),
                        new ChoiceView('USD', 'USD', $usdSymbol),
                        new ChoiceView('GBP', 'GBP', $gbpSymbol),
                    ]
                ],
                'submittedData' => 'UAH'
            ],
            'compact currency name, data from currencies_list option and additional currencies' => [
                'allowedCurrencies' => ['USD', 'UAH'],
                'inputOptions' => [
                    'compact' => true,
                    'currencies_list' => ['RUB'],
                    'additional_currencies' => ['GBP']
                ],
                'expectedOptions' => [
                    'compact' => true,
                    'choices' => [new ChoiceView('RUB', 'RUB', 'RUB'), new ChoiceView('GBP', 'GBP', 'GBP')]
                ],
                'submittedData' => 'GBP'
            ],
            'full currencies list' => [
                'allowedCurrencies' => ['USD', 'UAH'],
                'inputOptions' => ['full_currency_list' => true],
                'expectedOptions' => [
                    'full_currency_list' => true,
                    'choices' => $this->getChoiceViews(Currencies::getNames('en'))
                ],
                'submittedData' => 'GBP'
            ],
            'full currency name and data from system config' => [
                'allowedCurrencies' => ['UAH', 'USD'],
                'inputOptions' => [
                    'full_currency_name' => true
                ],
                'expectedOptions' => [
                    'compact' => false,
                    'full_currency_name' => true,
                    'choices' => [new ChoiceView('UAH', 'UAH', $uahName), new ChoiceView('USD', 'USD', $usdName)]
                ],
                'submittedData' => 'UAH'
            ],
            'full currency name overriden by compact option' => [
                'allowedCurrencies' => ['UAH', 'USD'],
                'inputOptions' => [
                    'full_currency_name' => true,
                    'compact' => true,
                ],
                'expectedOptions' => [
                    'compact' => true,
                    'full_currency_name' => false,
                    'choices' => [new ChoiceView('UAH', 'UAH', 'UAH'), new ChoiceView('USD', 'USD', 'USD')]
                ],
                'submittedData' => 'UAH'
            ],
        ];
    }

    private function getChoiceViews(array $legacyChoices): array
    {
        $choices = [];
        foreach ($legacyChoices as $key => $value) {
            $choices[] = new ChoiceView($key, $key, $value);
        }
        return $choices;
    }

    public function testInvalidTypeOfCurrenciesListOption()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The option "currencies_list" must be null or not empty array.');

        $this->factory->create(CurrencySelectionType::class, null, ['currencies_list' => 'string']);
    }

    public function testUnknownCurrency()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Found unknown currencies: CUR, TST.');

        $this->factory->create(CurrencySelectionType::class, null, ['currencies_list' => ['CUR', 'TST']]);
    }

    public function testInvalidTypeOfAdditionalCurrenciesOption()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The option "additional_currencies" must be null or array.');

        $this->factory->create(CurrencySelectionType::class, null, ['additional_currencies' => 'string']);
    }

    public function testGetName()
    {
        $this->assertEquals(CurrencySelectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }
}

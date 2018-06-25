<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Utils\CurrencyNameHelperStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Intl;

class CurrencySelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CurrencySelectionType
     */
    protected $formType;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CurrencyProviderInterface
     */
    protected $currencyProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Oro\Bundle\LocaleBundle\Model\LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper
     */
    protected $currencyNameHelper;


    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->setMethods([
                'getCurrencyList',
                'getDefaultCurrency',
                'getViewType'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->localeSettings = $this
            ->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->setMethods(['getCurrency', 'getLocale'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeSettings->expects($this->any())
            ->method('getLocale')
            ->willReturn(\Locale::getDefault());

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper */
        $this->currencyNameHelper = new CurrencyNameHelperStub();

        $this->formType = new CurrencySelectionType(
            $this->currencyProvider,
            $this->localeSettings,
            $this->currencyNameHelper
        );

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    CurrencySelectionType::class => $this->formType
                ],
                []
            ),
        ];
    }

    /**
     *
     * @dataProvider submitDataProvider
     *
     * @param array $allowedCurrencies
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param string $submittedData
     */
    public function testSubmit(
        array $allowedCurrencies,
        array $inputOptions,
        array $expectedOptions,
        $submittedData
    ) {
        $this->currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn($allowedCurrencies);

        $this->doTestForm($inputOptions, $expectedOptions, $submittedData);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function submitDataProvider()
    {
        $currencyBundle = Intl::getCurrencyBundle();
        $usdSymbol = $currencyBundle->getCurrencySymbol('USD');
        $gbpSymbol = $currencyBundle->getCurrencySymbol('GBP');
        $rubSymbol = $currencyBundle->getCurrencySymbol('RUB');
        $uahSymbol = $currencyBundle->getCurrencySymbol('UAH');

        $usdName = $currencyBundle->getCurrencyName('USD');
        $uahName = $currencyBundle->getCurrencyName('UAH');

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
                    'choices' => $this->getChoiceViews($currencyBundle->getCurrencyNames('en'))
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

    /**
     * @param array $legacyChoices
     *
     * @return array
     */
    protected function getChoiceViews(array $legacyChoices)
    {
        $choices = [];
        foreach ($legacyChoices as $key => $value) {
            $choices[] = new ChoiceView($key, $key, $value);
        }
        return $choices;
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage The option "currencies_list" must be null or not empty array.
     */
    public function testInvalidTypeOfCurrenciesListOption()
    {
        $this->factory->create(CurrencySelectionType::class, null, ['currencies_list' => 'string']);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage Found unknown currencies: CUR, TST.
     */
    public function testUnknownCurrency()
    {
        $this->factory->create(CurrencySelectionType::class, null, ['currencies_list' => ['CUR', 'TST']]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage The option "additional_currencies" must be null or array.
     */
    public function testInvalidTypeOfAdditionalCurrenciesOption()
    {
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

    /**
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param $submittedData
     * @return FormInterface
     */
    protected function doTestForm(array $inputOptions, array $expectedOptions, $submittedData)
    {
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
        $this->assertEquals($submittedData, $form->getData());

        return $form;
    }
}

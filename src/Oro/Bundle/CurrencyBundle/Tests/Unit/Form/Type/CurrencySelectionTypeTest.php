<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Intl;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Utils\CurrencyNameHelperStub;

class CurrencySelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CurrencySelectionType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\ConfigBundle\Config\ConfigManager
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\LocaleBundle\Model\LocaleSettings
     */
    protected $localeSettings;

    private $currencyNameHelper;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\CurrencyBundle\Config\CurrencyConfigManager')
            ->setMethods([
                'getCurrencyList',
                'getDefaultCurrency',
                'getViewType'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings = $this
            ->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->setMethods(['getCurrency', 'getLocale'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeSettings->expects($this->any())
            ->method('getLocale')
            ->willReturn(\Locale::getDefault());

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper */
        $this->currencyNameHelper = new CurrencyNameHelperStub();

        $this->formType = new CurrencySelectionType(
            $this->configManager,
            $this->localeSettings,
            $this->currencyNameHelper
        );
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity:)
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
        $hasCustomCurrencies = isset($inputOptions['currencies_list']) || !empty($inputOptions['full_currency_list']);
        $this->configManager->expects($hasCustomCurrencies ? $this->never() : $this->once())
            ->method('getCurrencyList')
            ->willReturn($allowedCurrencies);


        $form = $this->factory->create($this->formType, null, $inputOptions);
        $formConfig = $form->getConfig();
        $this->checkOptions($expectedOptions, $formConfig);

        if (!isset($inputOptions['full_currency_list']) || !$inputOptions['full_currency_list']) {
            $this->assertEquals($expectedOptions['choices'], $form->createView()->vars['choices']);
        }

        $this->assertNull($form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * @param array                     $expectedOptions
     * @param FormBuilderInterface  $formConfig
     */
    protected function checkOptions($expectedOptions, FormBuilderInterface $formConfig)
    {
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function submitDataProvider()
    {
        $currencyBundle = Intl::getCurrencyBundle();
        $usdName = $currencyBundle->getCurrencyName('USD');
        $gbpName = $currencyBundle->getCurrencyName('GBP');
        $rubName = $currencyBundle->getCurrencyName('RUB');
        $uahName = $currencyBundle->getCurrencyName('UAH');
        
        return [
            'full currency name and data from system config' => [
                'allowedCurrencies' => ['UAH', 'USD'],
                'inputOptions' => [],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [new ChoiceView('UAH', 'UAH', $uahName), new ChoiceView('USD', 'USD', $usdName)]
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
            'full currency name and data from currencies_list option' => [
                'allowedCurrencies' => ['USD', 'UAH'],
                'inputOptions' => [
                    'compact' => false,
                    'currencies_list' => ['RUB']
                ],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [ new ChoiceView('RUB', 'RUB', $rubName) ]
                ],
                'submittedData' => 'RUB'
            ],
            'full currency name, data from system config and additional currencies' => [
                'allowedCurrencies' => ['UAH', 'USD'],
                'inputOptions' => [
                    'additional_currencies' => ['GBP']
                ],
                'expectedOptions' => [
                    'compact' => false,
                    'choices' => [
                        new ChoiceView('UAH', 'UAH', $uahName),
                        new ChoiceView('USD', 'USD', $usdName),
                        new ChoiceView('GBP', 'GBP', $gbpName),
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
            ]
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
        $this->factory->create($this->formType, null, ['currencies_list' => 'string']);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage Found unknown currencies: CUR, TST.
     */
    public function testUnknownCurrency()
    {
        $this->factory->create($this->formType, null, ['currencies_list' => ['CUR', 'TST']]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     * @expectedExceptionMessage The option "additional_currencies" must be null or array.
     */
    public function testInvalidTypeOfAdditionalCurrenciesOption()
    {
        $this->factory->create($this->formType, null, ['additional_currencies' => 'string']);
    }

    public function testGetName()
    {
        $this->assertEquals(CurrencySelectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}

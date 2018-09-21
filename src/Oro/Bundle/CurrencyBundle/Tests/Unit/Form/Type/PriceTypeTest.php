<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        /* @var $currencyProvider \PHPUnit\Framework\MockObject\MockObject|CurrencyProviderInterface */
        $currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->will($this->returnValue(['USD', 'EUR']));

        /* @var $localeSettings \PHPUnit\Framework\MockObject\MockObject|LocaleSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper */
        $currencyNameHelper = $this
            ->getMockBuilder('Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper')
            ->disableOriginalConstructor()
            ->getMock();


        return [
            new PreloadedExtension(
                [
                    PriceType::class => PriceTypeGenerator::createPriceType($this),
                    CurrencySelectionType::class => new CurrencySelectionType(
                        $currencyProvider,
                        $localeSettings,
                        $currencyNameHelper
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @param bool $isValid
     * @param mixed $defaultData
     * @param array $submittedData
     * @param mixed $expectedData
     * @param array $options
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $defaultData, $submittedData, $expectedData, array $options = [])
    {
        $form = $this->factory->create(PriceType::class, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'price without value' => [
                'isValid'       => true,
                'defaultData'   => new Price(),
                'submittedData' => [],
                'expectedData'  => null
            ],
            'not numeric value' => [
                'isValid'       => false,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => 'test-value',
                    'currency' => 'USD'
                ],
                'expectedData'  => null,
            ],
            'value < 0' => [
                'isValid'       => false,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => -1,
                    'currency' => 'USD'
                ],
                'expectedData'  => (new Price())->setValue(-1)->setCurrency('USD')
            ],
            'price without currency' => [
                'isValid'       => false,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => 100
                ],
                'expectedData'  => (new Price())->setValue(100)
            ],
            'invalid currency' => [
                'isValid'       => false,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => 100,
                    'currency' => 'UAH'
                ],
                'expectedData'  => (new Price())->setValue(100)
            ],
            'price with value' => [
                'isValid'       => true,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => 100,
                    'currency' => 'USD'
                ],
                'expectedData'  => (new Price())->setValue(100)->setCurrency('USD')
            ],
            'price with precision' => [
                'isValid'       => true,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => 100.1234,
                    'currency' => 'USD'
                ],
                'expectedData'  => (new Price())->setValue(100.1234)->setCurrency('USD')
            ],
            'hidden price' => [
                'isValid'       => true,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => 100,
                    'currency' => 'EUR'
                ],
                'expectedData'  => (new Price())->setValue(100)->setCurrency('EUR'),
                [
                    'hide_currency' => true,
                    'default_currency' => 'USD'
                ]
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $formType = $this->factory->create(PriceType::class);
        $this->assertEquals(PriceType::NAME, $formType->getName());
    }

    public function testConfigureOptions()
    {
        /** @var $optionsResolverMock OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
        $optionsResolverMock = $this->getMockBuilder(OptionsResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PriceType $form */
        $form = new PriceType();
        $form->setDataClass(\stdClass::class);

        $optionsResolverMock->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                'data_class' => \stdClass::class,
                'hide_currency' => false,
                'additional_currencies' => null,
                'currencies_list' => null,
                'default_currency' => null,
                'full_currency_list' => false,
                'currency_empty_value' => 'oro.currency.currency.form.choose',
                'compact' => false,
                'validation_groups'=> ['Default'],
                'match_price_on_null' => true
                ]
            );

        $form->configureOptions($optionsResolverMock);
    }
}

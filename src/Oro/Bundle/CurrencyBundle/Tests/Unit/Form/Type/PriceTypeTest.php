<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class PriceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PriceType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = PriceTypeGenerator::createPriceType($this);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /* @var $currencyProvider \PHPUnit_Framework_MockObject_MockObject|CurrencyProviderInterface */
        $currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->will($this->returnValue(['USD', 'EUR']));

        /* @var $localeSettings \PHPUnit_Framework_MockObject_MockObject|LocaleSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper */
        $currencyNameHelper = $this
            ->getMockBuilder('Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper')
            ->disableOriginalConstructor()
            ->getMock();


        return [
            new PreloadedExtension(
                [CurrencySelectionType::NAME => new CurrencySelectionType(
                    $currencyProvider,
                    $localeSettings,
                    $currencyNameHelper
                )],
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
        $form = $this->factory->create($this->formType, $defaultData, $options);

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
        $this->assertEquals(PriceType::NAME, $this->formType->getName());
    }
}

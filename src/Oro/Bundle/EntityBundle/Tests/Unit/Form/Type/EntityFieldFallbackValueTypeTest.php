<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class EntityFieldFallbackValueTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configInterface;

    public function setUp()
    {
        parent::setUp();
        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)->disableOriginalConstructor()->getMock();
        $this->configInterface = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->configInterface);
    }

    public function testBuildFormThrowsMissingOptionException()
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $options = [];

        $this->setExpectedException(MissingOptionsException::class);
        $this->factory->create(new EntityFieldFallbackValueType($this->configProvider), $fallbackValue, $options);
    }

    public function testBuildFormPassesOptions()
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $options = $this->getDefaultTypeOptions();
        $options = array_merge(
            $options,
            [
                'value_options' => [
                    'label' => 'test_value_label',
                ],
                'fallback_options' => ['empty_value' => false],
                'use_fallback_options' => ['label' => 'test_use_fallback_label'],
            ]
        );

        $fallbackEntityFieldConfiguration = [
            'systemConfig' => [],
        ];

        $this->configInterface->expects($this->once())
            ->method('getValues')
            ->willReturn($fallbackEntityFieldConfiguration);
        $form = $this->factory->create(
            new EntityFieldFallbackValueType($this->configProvider),
            $fallbackValue,
            $options
        );

        $stringValueForm = $form->get('stringValue');
        $valueOptions = $stringValueForm->getConfig()->getOptions();
        $this->assertArrayHasKey('label', $valueOptions);
        $this->assertEquals($options['value_options']['label'], $valueOptions['label']);
        $this->assertEquals($options['value_type'], $stringValueForm->getConfig()->getType()->getName());

        $fallbackForm = $form->get('fallback');
        $fallbackOptions = $fallbackForm->getConfig()->getOptions();
        $this->assertArrayHasKey('empty_value', $fallbackOptions);
        $this->assertEquals($options['fallback_options']['empty_value'], $fallbackOptions['empty_value']);
        $this->assertEquals($options['fallback_type'], $fallbackForm->getConfig()->getType()->getName());

        $stringValueForm = $form->get('useFallback');
        $valueOptions = $stringValueForm->getConfig()->getOptions();
        $this->assertArrayHasKey('label', $valueOptions);
        $this->assertEquals($options['use_fallback_options']['label'], $valueOptions['label']);


    }

    public function testSubmitFallback()
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setFallback('testFallback')
            ->setUseFallback(true)
            ->setStringValue(null);

        $options = $this->getDefaultTypeOptions();

        $fallbackEntityFieldConfiguration = [
            'systemConfig' => [],
            'testFallback' => [],
        ];

        $this->configInterface->expects($this->once())
            ->method('getValues')
            ->willReturn($fallbackEntityFieldConfiguration);
        $form = $this->factory->create(
            new EntityFieldFallbackValueType($this->configProvider),
            $fallbackValue,
            $options
        );

        $requestData = ['fallback' => 'systemConfig'];
        $form->submit($requestData);

        $this->assertEquals($requestData['fallback'], $form->getData()->getFallback());
    }

    public function testSubmitValue()
    {

        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setFallback('testFallback')
            ->setUseFallback(true)
            ->setStringValue(null);

        $options = $this->getDefaultTypeOptions();
        $options = array_merge(
            $options,
            [
                'value_options' => [
                    'choices' => ['test1' => 'Test1', 'test2' => 'Test2'],
                ],
            ]
        );

        $fallbackEntityFieldConfiguration = [
            'systemConfig' => [],
            'testFallback' => [],
        ];

        $this->configInterface->expects($this->once())
            ->method('getValues')
            ->willReturn($fallbackEntityFieldConfiguration);
        $form = $this->factory->create(
            new EntityFieldFallbackValueType($this->configProvider),
            $fallbackValue,
            $options
        );

        $requestData = ['fallback' => null, 'useFallback' => false, 'stringValue' => 'test1'];
        $form->submit($requestData);
        $this->assertEquals($requestData['fallback'], $form->getData()->getFallback());
        $this->assertEquals($requestData['useFallback'], $form->getData()->isUseFallback());
        $this->assertEquals($requestData['stringValue'], $form->getData()->getStringValue());
    }

    public function testFallbackChoiceFilter()
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $options = $this->getDefaultTypeOptions();
        $options = array_merge(
            $options,
            [
                'fallback_options' => [
                    'choices' => [
                        'test1' => 'Test1',
                        'test2' => 'Test2',
                    ],
                ],
                'fallback_choice_filter' => function ($choices) {
                    unset($choices['test1']);

                    return $choices;
                },
            ]
        );

        $form = $this->factory->create(
            new EntityFieldFallbackValueType($this->configProvider),
            $fallbackValue,
            $options
        );

        $newChoices = $form->get('fallback')->getConfig()->getOption('choices');
        $this->assertArrayHasKey('test2', $newChoices);
        $this->assertArrayNotHasKey('test1', $newChoices);
    }

    /**
     * @return array
     */
    protected function getDefaultTypeOptions()
    {
        return [
            'value_type' => 'choice',
            'fallback_type' => 'choice',
            'parent_object' => new \stdClass(),
            'fallback_translation_prefix' => 'test_prefix',
            'fallback_options' => ['empty_value' => false],
        ];
    }
}

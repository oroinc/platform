<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Tests\Unit\Form\Stub\FallbackParentStub;
use Oro\Bundle\EntityBundle\Tests\Unit\Form\Stub\FallbackParentStubType;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class EntityFieldFallbackValueTypeTest extends FormIntegrationTestCase
{
    /** @var EntityFallbackResolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $fallbackResolver;

    public function testBuildFormThrowsMissingOptionException()
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $options = [];

        $this->setExpectedException(MissingOptionsException::class);
        $this->factory->create(new EntityFieldFallbackValueType($this->fallbackResolver), $fallbackValue, $options);
    }

    public function testOptionsCanBeOverridden()
    {
        $options = [
            'fallback' => [
                'fallback_translation_prefix' => 'xxx',
                'value_type' => IntegerType::class,
                'fallback_type' => IntegerType::class,
                'value_options' => ['scale' => 12],
                'fallback_options' => ['scale' => 13, 'required' => true],
                'use_fallback_options' => ['required' => true],
            ],
        ];
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $this->fallbackResolver->expects($this->any())
            ->method('getFallbackConfig')->willReturn([]);
        $form = $this->factory->create(new FallbackParentStubType(), $parentObject, $options);
        $fallbackFormType = $this->getChildForm($form);
        $this->assertEquals(
            'integer',
            $fallbackFormType->get('viewValue')->getConfig()->getType()->getName()
        );
        $this->assertEquals(
            'integer',
            $fallbackFormType->get('fallback')->getConfig()->getType()->getName()
        );
        $this->assertEquals(
            $options['fallback']['value_options']['scale'],
            $fallbackFormType->get('viewValue')->getConfig()->getOption('scale')
        );
        $this->assertEquals(
            $options['fallback']['fallback_options']['scale'],
            $fallbackFormType->get('fallback')->getConfig()->getOption('scale')
        );
        $this->assertEquals(
            false,
            $fallbackFormType->get('viewValue')->getConfig()->getOption('required')
        );
        $this->assertEquals(
            $options['fallback']['fallback_options']['required'],
            $fallbackFormType->get('fallback')->getConfig()->getOption('required')
        );
        $this->assertEquals(
            $options['fallback']['use_fallback_options']['required'],
            $fallbackFormType->get('useFallback')->getConfig()->getOption('required')
        );
    }

    public function testSystemConfigHasPriority()
    {
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $this->fallbackResolver->expects($this->any())
            ->method('getSystemConfigFormDescription')->willReturn(['type' => IntegerType::class]);
        $this->fallbackResolver->expects($this->any())
            ->method('getFallbackConfig')->willReturn([]);
        $form = $this->factory->create(new FallbackParentStubType(), $parentObject, $this->getDefaultOptions());
        $this->assertEquals(
            'integer',
            $this->getChildForm($form)->get('viewValue')->getConfig()->getType()->getName()
        );
    }

    public function testViewValueTypeFallbacksToBoolFieldConfig()
    {
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $this->fallbackResolver->expects($this->any())->method('getSystemConfigFormDescription')->willReturn([]);
        $this->fallbackResolver->expects($this->any())->method('getType')->willReturn('boolean');
        $this->fallbackResolver->expects($this->any())
            ->method('getFallbackConfig')->willReturn([]);
        $form = $this->factory->create(new FallbackParentStubType(), $parentObject, $this->getDefaultOptions());
        $this->assertEquals(
            'choice',
            $this->getChildForm($form)->get('viewValue')->getConfig()->getType()->getName()
        );
    }

    public function testViewValueTypeFallbacksToStringFieldConfig()
    {
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $this->fallbackResolver->expects($this->any())->method('getSystemConfigFormDescription')->willReturn([]);
        $this->fallbackResolver->expects($this->any())->method('getType')->willReturn('string');
        $this->fallbackResolver->expects($this->any())
            ->method('getFallbackConfig')->willReturn([]);
        $form = $this->factory->create(new FallbackParentStubType(), $parentObject, $this->getDefaultOptions());
        $this->assertEquals(
            'text',
            $this->getChildForm($form)->get('viewValue')->getConfig()->getType()->getName()
        );
    }

    public function testViewOptionsGetOverriddenBySystemConfig()
    {
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $systemConfig = [
            'options' => [
                'choices' => ['test1' => 'test1', 'test2' => 'test2'],
                'choices_as_values' => true,
            ],
        ];
        $this->fallbackResolver->expects($this->any())
            ->method('getSystemConfigFormDescription')
            ->willReturn($systemConfig);
        $this->fallbackResolver->expects($this->any())
            ->method('getFallbackConfig')->willReturn([]);
        $form = $this->factory->create(new FallbackParentStubType(), $parentObject, $this->getDefaultOptions());
        $formOptions = $this->getChildForm($form)->get('viewValue')->getConfig()->getOptions();
        $this->assertEquals(
            $systemConfig['options']['choices'],
            $formOptions['choices']
        );
    }

    public function testFallbackOptionsFiltersFallbacksIfNotSupported()
    {
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $fallbacks = ['testFallback1' => [], 'testFallback2' => []];
        $this->fallbackResolver->expects($this->any())
            ->method('getFallbackConfig')->willReturn($fallbacks);
        $this->fallbackResolver->expects($this->any())
            ->method('isFallbackSupported')
            ->will(
                $this->returnCallback(
                    function ($object, $fieldName, $fallbackId) {
                        switch ($fallbackId) {
                            case 'testFallback1':
                                return false;
                            case 'testFallback2':
                                return true;
                        }
                    }
                )
            );
        $form = $this->factory->create(new FallbackParentStubType(), $parentObject, $this->getDefaultOptions());
        $this->assertArrayHasKey(
            'testFallback2',
            $this->getChildForm($form)->get('fallback')->getConfig()->getOption('choices')
        );
    }

    public function testSubmitOwnValue()
    {
        $parentObject = new FallbackParentStub();
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setFallback('testFallback1');
        $parentObject->valueWithFallback = $fallbackValue;
        $fallbacks = ['testFallback1' => [], 'testFallback2' => []];
        $this->fallbackResolver->expects($this->any())->method('isFallbackSupported')->willReturn(true);
        $this->fallbackResolver->expects($this->any())->method('getFallbackConfig')->willReturn($fallbacks);

        $options = $this->getDefaultOptions();
        $options['fallback']['value_options']['choices'] = ['testValue' => 'testValue'];
        $form = $this->factory->create(new FallbackParentStubType(), $parentObject, $options);

        $requestData = [
            'valueWithFallback' => [
                'fallback' => 'testFallback2',
                'useFallback' => false,
                'viewValue' => 'testValue',
            ],
        ];
        $form->submit($requestData);
        /** @var EntityFieldFallbackValue $submittedFallbackValue */
        $submittedFallbackValue = $form->getData()->valueWithFallback;

        $this->assertNull($submittedFallbackValue->getFallback());
        $this->assertEquals($requestData['valueWithFallback']['useFallback'], $submittedFallbackValue->isUseFallback());
        $this->assertEquals($requestData['valueWithFallback']['viewValue'], $submittedFallbackValue->getOwnValue());
        $this->assertEquals($requestData['valueWithFallback']['viewValue'], $submittedFallbackValue->getScalarValue());
    }

    public function testSubmitFallback()
    {
        $parentObject = new FallbackParentStub();
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setFallback('testFallback1');
        $parentObject->valueWithFallback = $fallbackValue;

        $fallbacks = ['testFallback1' => [], 'testFallback2' => []];
        $this->fallbackResolver->expects($this->any())->method('isFallbackSupported')->willReturn(true);
        $this->fallbackResolver->expects($this->any())->method('getFallbackConfig')->willReturn($fallbacks);
        $form = $this->factory->create(new FallbackParentStubType(), $parentObject, $this->getDefaultOptions());
        $requestData = [
            'valueWithFallback' => [
                'fallback' => 'testFallback2',
                'useFallback' => true,
                'viewValue' => 'testvalue',
            ],
        ];
        $form->submit($requestData);
        /** @var EntityFieldFallbackValue $submittedFallbackValue */
        $submittedFallbackValue = $form->getData()->valueWithFallback;

        $this->assertEquals($requestData['valueWithFallback']['fallback'], $submittedFallbackValue->getFallback());
        $this->assertEquals($requestData['valueWithFallback']['useFallback'], $submittedFallbackValue->isUseFallback());
        $this->assertNull($submittedFallbackValue->getOwnValue());
    }

    /**
     * @param $form
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getChildForm($form)
    {
        return $form->get('valueWithFallback');
    }

    /**
     * @return array
     */
    protected function getDefaultOptions()
    {
        return [
            'fallback' => [
                'fallback_translation_prefix' => 'xxx',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $this->fallbackResolver = $this->getMockBuilder(EntityFallbackResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    EntityFieldFallbackValueType::NAME => new EntityFieldFallbackValueType($this->fallbackResolver),
                ],
                []
            ),
        ];
    }
}

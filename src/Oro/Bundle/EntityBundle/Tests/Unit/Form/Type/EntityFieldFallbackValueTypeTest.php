<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\EntityBundle\Tests\Unit\Form\Stub\FallbackParentStub;
use Oro\Bundle\EntityBundle\Tests\Unit\Form\Stub\FallbackParentStubType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;

class EntityFieldFallbackValueTypeTest extends FormIntegrationTestCase
{
    private EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject $fallbackResolver;

    public function testOptionsCanBeOverridden(): void
    {
        $options = [
            'fallback' => [
                'value_type' => IntegerType::class,
                'fallback_type' => IntegerType::class,
                'value_options' => [],
                'fallback_options' => ['required' => true],
                'use_fallback_options' => ['required' => true],
            ],
        ];
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $this->fallbackResolver->expects(self::any())
            ->method('getFallbackConfig')
            ->willReturn([]);
        $form = $this->factory->create(FallbackParentStubType::class, $parentObject, $options);
        $fallbackFormType = $this->getChildForm($form);
        self::assertInstanceOf(
            IntegerType::class,
            $fallbackFormType->get('scalarValue')->getConfig()->getType()->getInnerType()
        );
        self::assertInstanceOf(
            IntegerType::class,
            $fallbackFormType->get('fallback')->getConfig()->getType()->getInnerType()
        );
        self::assertEquals(
            false,
            $fallbackFormType->get('scalarValue')->getConfig()->getOption('required')
        );
        self::assertEquals(
            $options['fallback']['fallback_options']['required'],
            $fallbackFormType->get('fallback')->getConfig()->getOption('required')
        );
        self::assertEquals(
            $options['fallback']['use_fallback_options']['required'],
            $fallbackFormType->get('useFallback')->getConfig()->getOption('required')
        );
    }

    public function testSystemConfigHasPriority(): void
    {
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $this->fallbackResolver->expects(self::any())
            ->method('getSystemConfigFormDescription')
            ->willReturn(['type' => IntegerType::class]);
        $this->fallbackResolver->expects(self::any())
            ->method('getFallbackConfig')
            ->willReturn([]);
        $form = $this->factory->create(FallbackParentStubType::class, $parentObject, $this->getDefaultOptions());
        self::assertInstanceOf(
            IntegerType::class,
            $this->getChildForm($form)->get('scalarValue')->getConfig()->getType()->getInnerType()
        );
    }

    public function testScalarValueTypeFallbacksToBoolFieldConfig(): void
    {
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $this->fallbackResolver->expects(self::any())
            ->method('getSystemConfigFormDescription')
            ->willReturn([]);
        $this->fallbackResolver->expects(self::any())
            ->method('getType')
            ->willReturn('boolean');
        $this->fallbackResolver->expects(self::any())
            ->method('getFallbackConfig')
            ->willReturn([]);
        $form = $this->factory->create(FallbackParentStubType::class, $parentObject, $this->getDefaultOptions());

        self::assertInstanceOf(
            ChoiceType::class,
            $this->getChildForm($form)->get('scalarValue')->getConfig()->getType()->getInnerType()
        );
    }

    public function testScalarValueTypeFallbacksToBoolFieldConfigWhenThereIsNoConfig(): void
    {
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $this->fallbackResolver->expects(self::any())
            ->method('getSystemConfigFormDescription')
            ->willReturn([]);
        $this->fallbackResolver->expects(self::any())
            ->method('getType')
            ->willThrowException(new FallbackFieldConfigurationMissingException());
        $this->fallbackResolver->expects(self::any())
            ->method('getFallbackConfig')
            ->willReturn([]);
        $form = $this->factory->create(FallbackParentStubType::class, $parentObject, $this->getDefaultOptions());
        self::assertInstanceOf(
            ChoiceType::class,
            $this->getChildForm($form)->get('scalarValue')->getConfig()->getType()->getInnerType()
        );
    }

    public function testScalarValueTypeFallbacksToStringFieldConfig(): void
    {
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $this->fallbackResolver->expects(self::any())
            ->method('getSystemConfigFormDescription')
            ->willReturn([]);
        $this->fallbackResolver->expects(self::any())
            ->method('getType')
            ->willReturn('string');
        $this->fallbackResolver->expects(self::any())
            ->method('getFallbackConfig')
            ->willReturn([]);
        $form = $this->factory->create(FallbackParentStubType::class, $parentObject, $this->getDefaultOptions());
        self::assertInstanceOf(
            TextType::class,
            $this->getChildForm($form)->get('scalarValue')->getConfig()->getType()->getInnerType()
        );
    }

    public function testViewOptionsGetOverriddenBySystemConfig(): void
    {
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $systemConfig = [
            'options' => [
                'choices' => ['test1' => 'test1', 'test2' => 'test2']
            ],
        ];
        $this->fallbackResolver->expects(self::any())
            ->method('getSystemConfigFormDescription')
            ->willReturn($systemConfig);
        $this->fallbackResolver->expects(self::any())
            ->method('getFallbackConfig')
            ->willReturn([]);
        $form = $this->factory->create(FallbackParentStubType::class, $parentObject, $this->getDefaultOptions());
        $formOptions = $this->getChildForm($form)->get('scalarValue')->getConfig()->getOptions();
        self::assertEquals(
            $systemConfig['options']['choices'],
            $formOptions['choices']
        );
    }

    public function testFallbackOptionsFiltersFallbacksIfNotSupported(): void
    {
        $parentObject = new FallbackParentStub();
        $parentObject->valueWithFallback = new EntityFieldFallbackValue();
        $fallbacks = ['testFallback1' => [], 'testFallback2' => []];
        $this->fallbackResolver->expects(self::any())
            ->method('getFallbackConfig')
            ->willReturn($fallbacks);
        $this->fallbackResolver->expects(self::any())
            ->method('isFallbackSupported')
            ->willReturnCallback(function ($object, $fieldName, $fallbackId) {
                switch ($fallbackId) {
                    case 'testFallback1':
                        return false;
                    case 'testFallback2':
                        return true;
                }
            });
        $form = $this->factory->create(FallbackParentStubType::class, $parentObject, $this->getDefaultOptions());
        self::assertContains(
            'testFallback2',
            $this->getChildForm($form)->get('fallback')->getConfig()->getOption('choices')
        );
    }

    public function testSubmitOwnValue(): void
    {
        $parentObject = new FallbackParentStub();
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setFallback('testFallback1');
        $parentObject->valueWithFallback = $fallbackValue;
        $fallbacks = ['testFallback1' => [], 'testFallback2' => []];
        $this->fallbackResolver->expects(self::any())
            ->method('isFallbackSupported')
            ->willReturn(true);
        $this->fallbackResolver->expects(self::any())
            ->method('getFallbackConfig')
            ->willReturn($fallbacks);

        $options = $this->getDefaultOptions();
        $options['fallback']['value_options']['choices'] = ['testValue' => 'testValue'];
        $form = $this->factory->create(FallbackParentStubType::class, $parentObject, $options);

        $requestData = [
            'valueWithFallback' => [
                'useFallback' => false,
                'scalarValue' => 'testValue',
            ],
        ];
        $form->submit($requestData);
        /** @var EntityFieldFallbackValue $submittedFallbackValue */
        $submittedFallbackValue = $form->getData()->valueWithFallback;

        self::assertNull($submittedFallbackValue->getFallback());
        self::assertEquals($requestData['valueWithFallback']['scalarValue'], $submittedFallbackValue->getOwnValue());
        self::assertEquals(
            $requestData['valueWithFallback']['scalarValue'],
            $submittedFallbackValue->getScalarValue()
        );
    }

    public function testSubmitFallback(): void
    {
        $parentObject = new FallbackParentStub();
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setFallback('testFallback1');
        $parentObject->valueWithFallback = $fallbackValue;

        $fallbacks = ['testFallback1' => [], 'testFallback2' => []];
        $this->fallbackResolver->expects(self::any())
            ->method('isFallbackSupported')
            ->willReturn(true);
        $this->fallbackResolver->expects(self::any())
            ->method('getFallbackConfig')
            ->willReturn($fallbacks);
        $form = $this->factory->create(FallbackParentStubType::class, $parentObject, $this->getDefaultOptions());
        $requestData = [
            'valueWithFallback' => [
                'fallback' => 'testFallback2',
                'useFallback' => true,
                'scalarValue' => 'testvalue',
            ],
        ];
        $form->submit($requestData);
        /** @var EntityFieldFallbackValue $submittedFallbackValue */
        $submittedFallbackValue = $form->getData()->valueWithFallback;

        self::assertEquals($requestData['valueWithFallback']['fallback'], $submittedFallbackValue->getFallback());
        self::assertNull($submittedFallbackValue->getOwnValue());
    }

    private function getChildForm($form): FormInterface
    {
        return $form->get('valueWithFallback');
    }

    private function getDefaultOptions(): array
    {
        return ['fallback' => []];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $this->fallbackResolver = $this->createMock(EntityFallbackResolver::class);

        return [
            new PreloadedExtension(
                [
                    EntityFieldFallbackValueType::class => new EntityFieldFallbackValueType($this->fallbackResolver),
                ],
                []
            ),
        ];
    }
}

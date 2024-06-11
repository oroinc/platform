<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\FormBundle\Form\EventListener\DisableFieldsEventSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DisableFieldsEventSubscriberTest extends TestCase
{
    private ExpressionLanguage|MockObject $expressionLanguage;

    private DisableFieldsEventSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->expressionLanguage = $this->createMock(ExpressionLanguage::class);
        $propertyAccessor = new PropertyAccessor();

        $this->subscriber = new DisableFieldsEventSubscriber($this->expressionLanguage, $propertyAccessor);
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                FormEvents::PRE_SUBMIT => 'onPreSubmit',
                FormEvents::POST_SUBMIT => 'onPostSubmit',
            ],
            DisableFieldsEventSubscriber::getSubscribedEvents()
        );
    }

    public function testOnPreSubmitWhenNoConditions(): void
    {
        $form = $this->createMock(FormInterface::class);
        $data = ['sampleField' => 'sample_value'];
        $event = new FormEvent($form, $data);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('disable_fields_if')
            ->willReturn([]);

        $form
            ->expects(self::never())
            ->method('add');

        $this->expressionLanguage
            ->expects(self::never())
            ->method('evaluate');

        $this->subscriber->onPreSubmit($event);
    }

    public function testOnPreSubmitWhenNoField(): void
    {
        $form = $this->createMock(FormInterface::class);
        $data = ['sampleField' => 'sample_value'];
        $event = new FormEvent($form, $data);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $fieldName = 'missingField';
        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('disable_fields_if')
            ->willReturn([$fieldName => 'true']);

        $form
            ->expects(self::once())
            ->method('has')
            ->with($fieldName)
            ->willReturn(false);

        $form
            ->expects(self::never())
            ->method('add');

        $this->expressionLanguage
            ->expects(self::never())
            ->method('evaluate');

        $this->subscriber->onPreSubmit($event);
    }

    public function testOnPreSubmitWhenConditionFalse(): void
    {
        $form = $this->createMock(FormInterface::class);
        $data = ['sampleField' => 'sample_value'];
        $event = new FormEvent($form, $data);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $fieldName = 'sampleField';
        $condition = 'some.condition == true';
        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('disable_fields_if')
            ->willReturn([$fieldName => $condition]);

        $form
            ->expects(self::once())
            ->method('has')
            ->with($fieldName)
            ->willReturn(true);

        $this->expressionLanguage
            ->expects(self::once())
            ->method('evaluate')
            ->with($condition, ['form' => $form, 'data' => $data])
            ->willReturn(false);

        $form
            ->expects(self::never())
            ->method('add');

        $this->subscriber->onPreSubmit($event);
    }

    public function testOnPreSubmitWhenConditionTrue(): void
    {
        $form = $this->createMock(FormInterface::class);
        $data = ['sampleField' => 'sample_value'];
        $event = new FormEvent($form, $data);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $fieldName = 'sampleField';
        $condition = 'some.condition == true';
        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('disable_fields_if')
            ->willReturn([$fieldName => $condition]);

        $form
            ->expects(self::once())
            ->method('has')
            ->with($fieldName)
            ->willReturn(true);

        $formField = $this->createMock(FormInterface::class);
        $formFieldConfig = $this->createMock(FormConfigInterface::class);
        $formField
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formFieldConfig);

        $resolvedFormFieldType = $this->createMock(ResolvedFormTypeInterface::class);
        $formFieldConfig
            ->expects(self::once())
            ->method('getType')
            ->willReturn($resolvedFormFieldType);

        $resolvedFormFieldType
            ->expects(self::once())
            ->method('getInnerType')
            ->willReturn(new FormType());

        $form
            ->expects(self::once())
            ->method('get')
            ->with($fieldName)
            ->willReturn($formField);

        $this->expressionLanguage
            ->expects(self::once())
            ->method('evaluate')
            ->with($condition, ['form' => $form, 'data' => $data])
            ->willReturn(true);

        $form
            ->expects(self::once())
            ->method('add')
            ->with($fieldName, FormType::class, ['disabled' => true]);

        $this->subscriber->onPreSubmit($event);
    }

    public function testOnPostSubmitWhenNoConditions(): void
    {
        $form = $this->createMock(FormInterface::class);
        $data = ['sampleField' => 'sample_value'];
        $event = new FormEvent($form, $data);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('disable_fields_if')
            ->willReturn([]);

        $this->expressionLanguage
            ->expects(self::never())
            ->method('evaluate');

        $this->subscriber->onPostSubmit($event);
    }

    public function testOnPostSubmitWhenNoField(): void
    {
        $form = $this->createMock(FormInterface::class);
        $data = ['sampleField' => 'sample_value'];
        $event = new FormEvent($form, $data);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $fieldName = 'missingField';
        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('disable_fields_if')
            ->willReturn([$fieldName => 'true']);

        $form
            ->expects(self::once())
            ->method('has')
            ->with($fieldName)
            ->willReturn(false);

        $this->expressionLanguage
            ->expects(self::never())
            ->method('evaluate');

        $this->subscriber->onPostSubmit($event);
    }

    public function testOnPostSubmitWhenConditionFalse(): void
    {
        $form = $this->createMock(FormInterface::class);
        $data = ['sampleField' => 'sample_value'];
        $event = new FormEvent($form, $data);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $fieldName = 'sampleField';
        $condition = 'some.condition == true';
        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('disable_fields_if')
            ->willReturn([$fieldName => $condition]);

        $form
            ->expects(self::once())
            ->method('has')
            ->with($fieldName)
            ->willReturn(true);

        $this->expressionLanguage
            ->expects(self::once())
            ->method('evaluate')
            ->with($condition, ['form' => $form, 'data' => $data])
            ->willReturn(false);

        $this->subscriber->onPostSubmit($event);

        self::assertEquals(['sampleField' => 'sample_value'], $data);
    }

    public function testOnPostSubmitWhenConditionTrue(): void
    {
        $form = $this->createMock(FormInterface::class);
        $data = (object)['sampleField' => 'sample_value'];
        $event = new FormEvent($form, $data);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $fieldName = 'sampleField';
        $condition = 'some.condition == true';
        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('disable_fields_if')
            ->willReturn([$fieldName => $condition]);

        $form
            ->expects(self::once())
            ->method('has')
            ->with($fieldName)
            ->willReturn(true);

        $this->expressionLanguage
            ->expects(self::once())
            ->method('evaluate')
            ->with($condition, ['form' => $form, 'data' => $data])
            ->willReturn(true);

        $this->subscriber->onPostSubmit($event);

        self::assertEquals((object)['sampleField' => null], $data);
    }
}

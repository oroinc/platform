<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\SetDefaultEnumValue;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEntityWithEnum;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SetDefaultEnumValueTest extends CustomizeFormDataProcessorTestCase
{
    private const string ENUM_CODE = 'test_enum';

    private EnumOptionsProvider&MockObject $enumOptionsProvider;
    private DoctrineHelper&MockObject $doctrineHelper;
    private TestEntityWithEnum $entity;
    private SetDefaultEnumValue $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new SetDefaultEnumValue(
            $this->enumOptionsProvider,
            $this->doctrineHelper,
            PropertyAccess::createPropertyAccessor(),
            'singleEnumField',
            self::ENUM_CODE
        );

        $this->entity = new TestEntityWithEnum();
        $this->context->setClassName(TestEntityWithEnum::class);
        $this->context->setData($this->entity);
    }

    private function getForm(bool $withEnumField = true): FormInterface
    {
        $formBuilder = $this->createFormBuilder()->create(
            '',
            FormType::class,
            ['data_class' => TestEntityWithEnum::class]
        );
        if ($withEnumField) {
            $formBuilder->add('singleEnumField', TextType::class);
        }

        return $formBuilder->getForm();
    }

    public function testProcessWhenFormDoesNotHaveEnumField(): void
    {
        $this->doctrineHelper->expects(self::never())
            ->method('isNewEntity');
        $this->enumOptionsProvider->expects(self::never())
            ->method('getDefaultEnumOptionByCode');

        $this->context->setForm($this->getForm(false));
        $this->processor->process($this->context);
    }

    public function testProcessWhenFormHasSubmittedEnumField(): void
    {
        $form = $this->getForm();
        $form->submit(['singleEnumField' => null]);
        self::assertTrue($form->get('singleEnumField')->isSubmitted());

        $this->doctrineHelper->expects(self::never())
            ->method('isNewEntity');
        $this->enumOptionsProvider->expects(self::never())
            ->method('getDefaultEnumOptionByCode');

        $this->context->setForm($form);
        $this->processor->process($this->context);
    }

    public function testProcessForExistingEntityWithoutValueForEnumField(): void
    {
        $this->doctrineHelper->expects(self::once())
            ->method('isNewEntity')
            ->with(self::identicalTo($this->entity))
            ->willReturn(false);
        $this->enumOptionsProvider->expects(self::never())
            ->method('getDefaultEnumOptionByCode');

        $this->context->setForm($this->getForm());
        $this->processor->process($this->context);

        self::assertNull($this->entity->getSingleEnumField());
    }

    public function testProcessForNewEntityWithValueForEnumField(): void
    {
        $value = new TestEnumValue('test', 'Value 1', 'val1');
        $this->entity->setSingleEnumField($value);

        $this->doctrineHelper->expects(self::once())
            ->method('isNewEntity')
            ->with(self::identicalTo($this->entity))
            ->willReturn(true);
        $this->enumOptionsProvider->expects(self::never())
            ->method('getDefaultEnumOptionByCode');

        $this->context->setForm($this->getForm());
        $this->processor->process($this->context);

        self::assertSame($value, $this->entity->getSingleEnumField());
    }

    public function testProcessForNewEntityWithoutValueForEnumFieldAndEnumDoesNotHaveDefaultValue(): void
    {
        $this->doctrineHelper->expects(self::once())
            ->method('isNewEntity')
            ->with(self::identicalTo($this->entity))
            ->willReturn(true);
        $this->enumOptionsProvider->expects(self::once())
            ->method('getDefaultEnumOptionByCode')
            ->with(self::ENUM_CODE)
            ->willReturn(null);

        $this->context->setForm($this->getForm());
        $this->processor->process($this->context);

        self::assertNull($this->entity->getSingleEnumField());
    }

    public function testProcessForNewEntityWithoutValueForEnumFieldAndEnumHasDefaultValue(): void
    {
        $defaultValue = new TestEnumValue('test', 'Value 1', 'val1');

        $this->doctrineHelper->expects(self::once())
            ->method('isNewEntity')
            ->with(self::identicalTo($this->entity))
            ->willReturn(true);
        $this->enumOptionsProvider->expects(self::once())
            ->method('getDefaultEnumOptionByCode')
            ->with(self::ENUM_CODE)
            ->willReturn($defaultValue);

        $this->context->setForm($this->getForm());
        $this->processor->process($this->context);

        self::assertSame($defaultValue, $this->entity->getSingleEnumField());
    }
}

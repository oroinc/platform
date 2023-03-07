<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\SetDefaultEnumValue;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEntityWithEnum;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SetDefaultEnumValueTest extends CustomizeFormDataProcessorTestCase
{
    private const ENUM_CODE = 'test_enum';

    /** @var EnumValueProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enumValueProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var TestEntityWithEnum */
    private $entity;

    /** @var SetDefaultEnumValue */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enumValueProvider = $this->createMock(EnumValueProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new SetDefaultEnumValue(
            $this->enumValueProvider,
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

    public function testProcessWhenFormDoesNotHaveEnumField()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('isNewEntity');
        $this->enumValueProvider->expects(self::never())
            ->method('getDefaultEnumValueByCode');

        $this->context->setForm($this->getForm(false));
        $this->processor->process($this->context);
    }

    public function testProcessWhenFormHasSubmittedEnumField()
    {
        $form = $this->getForm();
        $form->submit(['singleEnumField' => null]);
        self::assertTrue($form->get('singleEnumField')->isSubmitted());

        $this->doctrineHelper->expects(self::never())
            ->method('isNewEntity');
        $this->enumValueProvider->expects(self::never())
            ->method('getDefaultEnumValueByCode');

        $this->context->setForm($form);
        $this->processor->process($this->context);
    }

    public function testProcessForExistingEntityWithoutValueForEnumField()
    {
        $this->doctrineHelper->expects(self::once())
            ->method('isNewEntity')
            ->with(self::identicalTo($this->entity))
            ->willReturn(false);
        $this->enumValueProvider->expects(self::never())
            ->method('getDefaultEnumValueByCode');

        $this->context->setForm($this->getForm());
        $this->processor->process($this->context);

        self::assertNull($this->entity->getSingleEnumField());
    }

    public function testProcessForNewEntityWithValueForEnumField()
    {
        $value = new TestEnumValue('val1', 'Value 1');
        $this->entity->setSingleEnumField($value);

        $this->doctrineHelper->expects(self::once())
            ->method('isNewEntity')
            ->with(self::identicalTo($this->entity))
            ->willReturn(true);
        $this->enumValueProvider->expects(self::never())
            ->method('getDefaultEnumValueByCode');

        $this->context->setForm($this->getForm());
        $this->processor->process($this->context);

        self::assertSame($value, $this->entity->getSingleEnumField());
    }

    public function testProcessForNewEntityWithoutValueForEnumFieldAndEnumDoesNotHaveDefaultValue()
    {
        $this->doctrineHelper->expects(self::once())
            ->method('isNewEntity')
            ->with(self::identicalTo($this->entity))
            ->willReturn(true);
        $this->enumValueProvider->expects(self::once())
            ->method('getDefaultEnumValueByCode')
            ->with(self::ENUM_CODE)
            ->willReturn(null);

        $this->context->setForm($this->getForm());
        $this->processor->process($this->context);

        self::assertNull($this->entity->getSingleEnumField());
    }

    public function testProcessForNewEntityWithoutValueForEnumFieldAndEnumHasDefaultValue()
    {
        $defaultValue = new TestEnumValue('val1', 'Value 1');

        $this->doctrineHelper->expects(self::once())
            ->method('isNewEntity')
            ->with(self::identicalTo($this->entity))
            ->willReturn(true);
        $this->enumValueProvider->expects(self::once())
            ->method('getDefaultEnumValueByCode')
            ->with(self::ENUM_CODE)
            ->willReturn($defaultValue);

        $this->context->setForm($this->getForm());
        $this->processor->process($this->context);

        self::assertSame($defaultValue, $this->entity->getSingleEnumField());
    }
}

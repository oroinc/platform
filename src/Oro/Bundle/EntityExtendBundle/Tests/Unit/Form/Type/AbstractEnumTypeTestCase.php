<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Form\Type\AbstractEnumType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Fixtures\TestEntity;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractEnumTypeTestCase extends TypeTestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->doctrine = $this->getMockForAbstractClass(
            ManagerRegistry::class,
            [],
            '',
            true,
            true,
            true,
            ['anything', 'getRepository']
        );

        parent::setUp();
    }

    public function doTestBuildForm(AbstractEnumType $type)
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$type, 'preSetData']);

        $type->buildForm($builder, []);
    }

    public function doTestPreSetDataForExistingEntity(AbstractEnumType $type)
    {
        $form = $this->createMock(FormInterface::class);

        $parentFormData = new TestEntity('123');

        $parentForm = $this->expectFormWillReturnParentForm($form);
        $parentFormConfig = $this->expectFormWillReturnFormConfig($parentForm);
        $this->expectFormConfigWillReturnOptions(
            $parentFormConfig,
            [
                ['data_class', null, get_class($parentFormData)]
            ]
        );
        $this->expectFormWillReturnData($parentForm, $parentFormData);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->never())
            ->method('setData');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForNullEntity(AbstractEnumType $type)
    {
        $form = $this->createMock(FormInterface::class);

        $parentForm = $this->expectFormWillReturnParentForm($form);
        $parentFormConfig = $this->expectFormWillReturnFormConfig($parentForm);
        $this->expectFormConfigWillReturnOptions(
            $parentFormConfig,
            [
                ['data_class', null, 'TestEntity']
            ]
        );
        $this->expectFormWillReturnData($parentForm, null);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->never())
            ->method('setData');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForFormWithoutDataClass(AbstractEnumType $type)
    {
        $form = $this->createMock(FormInterface::class);

        $parentForm = $this->expectFormWillReturnParentForm($form);
        $parentFormConfig = $this->expectFormWillReturnFormConfig($parentForm);
        $this->expectFormConfigWillReturnOptions(
            $parentFormConfig,
            [
                ['data_class', null, null]
            ]
        );

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->never())
            ->method('setData');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForNewEntityKeepExistingValue(AbstractEnumType $type)
    {
        $enumValueClassName = TestEnumValue::class;

        $entity = new TestEntity();
        $entity->setValue($this->createMock($enumValueClassName));

        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->expectFormWillReturnFormConfig($form);
        $this->expectFormConfigWillReturnOptions(
            $formConfig,
            [
                ['multiple', null, false]
            ]
        );

        $parentForm = $this->expectFormWillReturnParentForm($form);
        $parentFormConfig = $this->expectFormWillReturnFormConfig($parentForm);

        $this->expectFormConfigWillReturnOptions(
            $parentFormConfig,
            [
                ['data_class', null, 'TestEntity']
            ]
        );

        $this->expectFormWillReturnData($parentForm, $entity);

        $form->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn('value'); // name of property TestEntity::$value

        $this->doctrine->expects($this->never())
            ->method('anything');

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->never())
            ->method('setData');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForNewEntity(AbstractEnumType $type)
    {
        $enumValueClassName = 'Test\EnumValue';

        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->expectFormWillReturnFormConfig($form);

        $parentForm = $this->expectFormWillReturnParentForm($form);
        $parentFormConfig = $this->expectFormWillReturnFormConfig($parentForm);
        $this->expectFormConfigWillReturnOptions(
            $parentFormConfig,
            [
                ['data_class', null, 'TestEntity']
            ]
        );

        $this->expectFormWillReturnData($parentForm, new TestEntity());

        // name of property TestEntity::$value
        $form->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn('value');

        $this->expectFormConfigWillReturnOptions(
            $formConfig,
            [
                ['class', null, $enumValueClassName],
                ['multiple', null, false]
            ]
        );

        $this->setExpectationsForLoadDefaultEnumValues($enumValueClassName, ['val1']);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->once())
            ->method('setData')
            ->with('val1');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForNewEntityWithMultiEnum(AbstractEnumType $type)
    {
        $enumValueClassName = 'Test\EnumValue';

        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->expectFormWillReturnFormConfig($form);
        $this->expectFormConfigWillReturnOptions(
            $formConfig,
            [
                ['class', null, $enumValueClassName],
                ['multiple', null, true]
            ]
        );

        $parentForm = $this->expectFormWillReturnParentForm($form);
        $parentFormConfig = $this->expectFormWillReturnFormConfig($parentForm);

        $this->expectFormConfigWillReturnOptions(
            $parentFormConfig,
            [
                ['data_class', null, 'TestEntity']
            ]
        );

        $data = new TestEntity();
        $data->setValue(new ArrayCollection());

        $this->expectFormWillReturnData($parentForm, $data);

        // name of property TestEntity::$value
        $form->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn('value');

        $this->setExpectationsForLoadDefaultEnumValues(
            $enumValueClassName,
            ['val1', 'val2']
        );

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->once())
            ->method('setData')
            ->with(['val1', 'val2']);

        $type->preSetData($event);
    }

    protected function doTestConfigureOptions(
        AbstractEnumType $type,
        OptionsResolver $resolver,
        string $enumCode,
        bool $multiple = false,
        bool $expanded = false,
        array $options = []
    ): array {
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);

        // AbstractEnumType require class to be instance of AbstractEnumValue
        // This may be achieved with Stub. Stub namespace does not reflect Stub path. So we have to load it manually
        $fileName = ExtendHelper::getShortClassName($enumValueClassName) . '.php';
        require_once(realpath(__DIR__ . DIRECTORY_SEPARATOR . 'Stub' . DIRECTORY_SEPARATOR . $fileName));
        $enumConfig = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('multiple', $multiple);
        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($enumConfigProvider);
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->willReturn($enumConfig);

        $type->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve(
            array_merge(
                $options,
                [
                    'enum_code' => $enumCode,
                    'expanded'  => $expanded
                ]
            )
        );

        $this->assertEquals($multiple, $resolvedOptions['multiple']);
        $this->assertEquals($expanded, $resolvedOptions['expanded']);
        $this->assertEquals($enumCode, $resolvedOptions['enum_code']);
        $this->assertEquals($enumValueClassName, $resolvedOptions['class']);
        $this->assertEquals('name', $resolvedOptions['choice_label']);
        $this->assertNotNull($resolvedOptions['query_builder']);

        unset(
            $resolvedOptions['multiple'],
            $resolvedOptions['expanded'],
            $resolvedOptions['enum_code'],
            $resolvedOptions['class'],
            $resolvedOptions['choice_label'],
            $resolvedOptions['query_builder']
        );

        return $resolvedOptions;
    }

    protected function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'multiple' => false,
            'expanded' => false
        ]);

        return $resolver;
    }

    protected function expectFormWillReturnParentForm(
        FormInterface|\PHPUnit\Framework\MockObject\MockObject $form,
        FormInterface|\PHPUnit\Framework\MockObject\MockObject $parentForm = null
    ): FormInterface|\PHPUnit\Framework\MockObject\MockObject {
        if (!$parentForm) {
            $parentForm = $this->createMock(FormInterface::class);
        }

        $form->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm);

        return $parentForm;
    }

    protected function expectFormWillReturnData(
        FormInterface|\PHPUnit\Framework\MockObject\MockObject $form,
        mixed $data
    ): void {
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($data);
    }

    protected function expectFormWillReturnFormConfig(
        FormInterface|\PHPUnit\Framework\MockObject\MockObject $form,
        FormConfigInterface|\PHPUnit\Framework\MockObject\MockObject $formConfig = null
    ): FormConfigInterface|\PHPUnit\Framework\MockObject\MockObject {
        if (!$formConfig) {
            $formConfig = $this->createMock(FormConfigInterface::class);
        }

        $form->expects($this->atLeastOnce())
            ->method('getConfig')
            ->willReturn($formConfig);

        return $formConfig;
    }

    protected function expectFormConfigWillReturnOptions(
        \PHPUnit\Framework\MockObject\MockObject $formConfig,
        array $optionsValueMap
    ): void {
        $formConfig->expects($this->atLeastOnce())
            ->method('getOption')
            ->willReturnMap($optionsValueMap);
    }

    protected function setExpectationsForLoadDefaultEnumValues(string $enumValueClassName, array $defaultValues): void
    {
        $repo = $this->createMock(EnumValueRepository::class);
        $repo->expects($this->once())
            ->method('getDefaultValues')
            ->willReturn($defaultValues);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with($enumValueClassName)
            ->willReturn($repo);
    }
}

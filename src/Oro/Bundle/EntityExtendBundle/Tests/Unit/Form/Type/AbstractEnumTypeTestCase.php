<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityExtendBundle\Form\Type\AbstractEnumType;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Fixtures\TestEntity;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AbstractEnumTypeTestCase extends TypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockForAbstractClass(
            'Doctrine\Common\Persistence\ManagerRegistry',
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
        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $builder->expects($this->at(0))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$type, 'preSetData']);

        $type->buildForm($builder, []);
    }

    public function doTestPreSetDataForExistingEntity(AbstractEnumType $type)
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

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

        $event = $this->getFormEventMock2($form);
        $event->expects($this->never())
            ->method('setData');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForNullEntity(AbstractEnumType $type)
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $parentForm = $this->expectFormWillReturnParentForm($form);
        $parentFormConfig = $this->expectFormWillReturnFormConfig($parentForm);
        $this->expectFormConfigWillReturnOptions(
            $parentFormConfig,
            [
                ['data_class', null, 'TestEntity']
            ]
        );
        $this->expectFormWillReturnData($parentForm, null);

        $event = $this->getFormEventMock2($form);
        $event->expects($this->never())
            ->method('setData');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForFormWithoutDataClass(AbstractEnumType $type)
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $parentForm = $this->expectFormWillReturnParentForm($form);
        $parentFormConfig = $this->expectFormWillReturnFormConfig($parentForm);
        $this->expectFormConfigWillReturnOptions(
            $parentFormConfig,
            [
                ['data_class', null, null]
            ]
        );

        $event = $this->getFormEventMock2($form);
        $event->expects($this->never())
            ->method('setData');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForNewEntityKeepExistingValue(AbstractEnumType $type)
    {
        $enumValueClassName = 'Test\EnumValue';

        $entity = new TestEntity();
        $entity->setValue($this->getMock($enumValueClassName));

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
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
            ->will($this->returnValue('value')); // name of property TestEntity::$value

        $event = $this->getFormEventMock2($form);

        $this->doctrine->expects($this->never())
            ->method('anything');

        $event->expects($this->never())
            ->method('setData');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForNewEntity(AbstractEnumType $type)
    {
        $enumValueClassName = 'Test\EnumValue';

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
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
            ->will($this->returnValue('value'));

        $this->expectFormConfigWillReturnOptions(
            $formConfig,
            [
                ['class', null, $enumValueClassName],
                ['multiple', null, false]
            ]
        );

        $this->setExpectationsForLoadDefaultEnumValues($enumValueClassName, ['val1']);

        $event = $this->getFormEventMock2($form);

        $event->expects($this->once())
            ->method('setData')
            ->with('val1');

        $type->preSetData($event);
    }

    public function doTestPreSetDataForNewEntityWithMultiEnum(AbstractEnumType $type)
    {
        $enumValueClassName = 'Test\EnumValue';

        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
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
            ->will($this->returnValue('value'));

        $event = $this->getFormEventMock2($form);

        $this->setExpectationsForLoadDefaultEnumValues(
            $enumValueClassName,
            ['val1', 'val2']
        );

        $event->expects($this->once())
            ->method('setData')
            ->with(['val1', 'val2']);

        $type->preSetData($event);
    }

    protected function doTestSetDefaultOptions(
        AbstractEnumType $type,
        OptionsResolver $resolver,
        $enumCode,
        $multiple = false,
        $expanded = false,
        array $options = []
    ) {
        $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);

        // AbstractEnumType require class to be instance of AbstractEnumValue
        // This may be achieved with Stub. Stub namespace does not reflect Stub path. So we have to load it manually
        $fileName = ExtendHelper::getShortClassName($enumValueClassName) . '.php';
        require_once(realpath(__DIR__ . DIRECTORY_SEPARATOR . 'Stub' . DIRECTORY_SEPARATOR . $fileName));
        $enumConfig         = new Config(new EntityConfigId('enum', $enumValueClassName));
        $enumConfig->set('multiple', $multiple);
        $enumConfigProvider = $this->getConfigProviderMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->will($this->returnValue($enumConfigProvider));
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($enumValueClassName)
            ->will($this->returnValue($enumConfig));

        $type->setDefaultOptions($resolver);

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
        $this->assertEquals('name', $resolvedOptions['property']);
        $this->assertNotNull($resolvedOptions['query_builder']);

        unset(
            $resolvedOptions['multiple'],
            $resolvedOptions['expanded'],
            $resolvedOptions['enum_code'],
            $resolvedOptions['class'],
            $resolvedOptions['property'],
            $resolvedOptions['query_builder']
        );

        return $resolvedOptions;
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'multiple' => false,
                'expanded' => false
            ]
        );

        return $resolver;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|null $form
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFormEventMock2($form)
    {
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));

        return $event;
    }

    /**
     * @param mixed                                         $entity
     * @param \PHPUnit_Framework_MockObject_MockObject|null $form
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFormEventMock($entity = null, $form = null)
    {
        if (!$form) {
            $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        }
        $rootForm = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $form->expects($this->once())
            ->method('getRoot')
            ->will($this->returnValue($rootForm));
        $rootForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($entity));

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));

        return $event;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $form
     * @param \PHPUnit_Framework_MockObject_MockObject|null $parentForm
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function expectFormWillReturnParentForm(
        \PHPUnit_Framework_MockObject_MockObject $form,
        \PHPUnit_Framework_MockObject_MockObject $parentForm = null
    ) {
        if (!$parentForm) {
            $parentForm = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        }

        $form->expects($this->once())
            ->method('getParent')
            ->will($this->returnValue($parentForm));

        return $parentForm;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $form
     * @param mixed $data
     */
    protected function expectFormWillReturnData(\PHPUnit_Framework_MockObject_MockObject $form, $data)
    {
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $form
     * @param \PHPUnit_Framework_MockObject_MockObject|null $formConfig
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function expectFormWillReturnFormConfig(
        \PHPUnit_Framework_MockObject_MockObject $form,
        \PHPUnit_Framework_MockObject_MockObject $formConfig = null
    ) {
        if (!$formConfig) {
            $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        }

        $form->expects($this->atLeastOnce())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));

        return $formConfig;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $formConfig
     * @param array $optionsValueMap
     */
    protected function expectFormConfigWillReturnOptions(
        \PHPUnit_Framework_MockObject_MockObject $formConfig,
        array $optionsValueMap
    ) {
        $formConfig->expects($this->atLeastOnce())
            ->method('getOption')
            ->will(
                $this->returnValueMap($optionsValueMap)
            );
    }

    /**
     * @param string $enumValueClassName
     * @param array  $defaultValues
     */
    protected function setExpectationsForLoadDefaultEnumValues($enumValueClassName, array $defaultValues)
    {
        $repo = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getDefaultValues')
            ->will($this->returnValue($defaultValues));

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with($enumValueClassName)
            ->will($this->returnValue($repo));
    }
}

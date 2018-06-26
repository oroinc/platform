<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Extension;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Extension\DynamicAttributesExtension;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Util\DynamicFieldsHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;

class DynamicAttributesExtensionTest extends TypeTestCase
{
    const DATA_CLASS = TestActivityTarget::class;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeManager;

    /**
     * @var AttributeConfigHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeConfigHelper;

    /**
     * @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeEntityConfig;

    /**
     * @var DynamicFieldsHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dynamicFieldsHelper;

    /**
     * @var DynamicAttributesExtension
     */
    private $extension;

    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeManager = $this->getMockBuilder(AttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeEntityConfig = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeConfigHelper = $this->getMockBuilder(AttributeConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dynamicFieldsHelper = $this->getMockBuilder(DynamicFieldsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new DynamicAttributesExtension(
            $this->configManager,
            $this->doctrineHelper,
            $this->attributeManager,
            $this->attributeConfigHelper,
            $this->dynamicFieldsHelper
        );
    }

    /**
     * @return array
     */
    public function notApplicableDataProvider()
    {
        return [
            'no data_class option' => [
                'options' => ['data_class' => null]
            ],
            'disabled extension' => [
                'options' => ['data_class' => \stdClass::class, 'enable_attributes' => false]
            ]
        ];
    }

    /**
     * @dataProvider notApplicableDataProvider
     *
     * @param array $options
     */
    public function testBuildFormWhenNotApplicable(array $options)
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->expects($this->never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, $options);
    }

    public function testBuildForm()
    {
        $viewConfigProvider = $this->getViewConfigProvider();
        $formConfigProvider = $this->getFormConfigProvider();
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->withConsecutive(
                ['view'],
                ['form']
            )
            ->willReturnOnConsecutiveCalls(
                $viewConfigProvider,
                $formConfigProvider
            );
        $this->expectsApplicable();
        
        $viewConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::DATA_CLASS, 'attribute')
            ->willReturn(
                new Config(
                    new FieldConfigId('view', self::DATA_CLASS, 'attribute'),
                    ['priority' => 1]
                )
            );

        $this->attributeConfigHelper
            ->expects($this->exactly(2))
            ->method('isFieldAttribute')
            ->willReturnOnConsecutiveCalls(false, true);

        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->expects($this->exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [FormEvents::PRE_SET_DATA, [$this->extension, 'onPreSetData'], 0],
                [FormEvents::PRE_SUBMIT, [$this->extension, 'onPreSubmit'], 0]
            );

        $this->extension->buildForm($builder, ['data_class' => self::DATA_CLASS, 'enable_attributes' => true]);
    }

    /**
     * @return array
     */
    public function preSetDataProviderNoAdd()
    {
        return [
            'null entity' => [
               'entity' => null,
            ],
            'no family entity' => [
                'entity' => new TestActivityTarget(),
            ],
            'no fields' => [
                'entity' => $this->getEntityWithFamily(),
            ]
        ];
    }

    /**
     * @dataProvider preSetDataProviderNoAdd
     * @param null|TestActivityTarget $entity
     */
    public function testOnPreSetDataNoAdd($entity)
    {
        $form = $this->getForm();
        $form->expects($this->never())
            ->method('add');
        $this->attributeManager->expects($this->never())
            ->method('getAttributesByFamily');

        $event = new FormEvent($form, $entity);
        $this->extension->onPreSetData($event);
    }

    /**
     * @return array
     */
    public function preSubmitProviderNoAdd()
    {
        return [
            'has family' => [
                'data' => ['attributeFamily' => 1],
                'family' => new AttributeFamily(),
            ],
            'no family in data' => [
                'data' => [],
                'family' => null,
            ],
        ];
    }

    /**
     * @dataProvider preSubmitProviderNoAdd
     * @param array $data
     */
    public function testOnPreSubmitNoAdd(array $data)
    {
        $form = $this->getForm();
        $form->expects($this->never())
            ->method('add');
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepositoryForClass');

        $event = new FormEvent($form, $data);
        $this->extension->onPreSetData($event);
    }

    /**
     * @return array
     */
    public function addAttributesDataProvider()
    {
        return [
            'one item' => [
                'fields' => ['attributeName' => 1],
                'attributes' => [new FieldConfigModel('attributeName')],
                'expectAdds' => 1,
            ],
            'two items' => [
                'fields' => ['attributeName' => 1, 'attributeName2' => 2],
                'attributes' => [new FieldConfigModel('attributeName'), new FieldConfigModel('attributeName2')],
                'expectAdds' => 2,
            ],
            'one item not from family' => [
                'fields' => ['attributeName' => 1, 'attributeName2' => 2],
                'attributes' => [new FieldConfigModel('attributeName')],
                'expectAdds' => 1,
            ]
        ];
    }

    /**
     * @dataProvider addAttributesDataProvider
     * @param array $fields
     * @param array $attributes
     * @param integer $expectAdds
     */
    public function testOnPreSetData(array $fields, array $attributes, $expectAdds)
    {
        $entity = $this->getEntityWithFamily();
        $form = $this->getForm();

        $this->setSecurityValue($this->extension, 'fields', [get_class($entity) => $fields]);
        
        $this->attributeManager->expects($this->once())
            ->method('getAttributesByFamily')
            ->with($entity->getAttributeFamily())
            ->willReturn($attributes);
        $form->expects($this->any())
            ->method('has')
            ->willReturn(false);
        $form->expects($this->exactly($expectAdds))
            ->method('add');
        
        $event = new FormEvent($form, $entity);
        $this->extension->onPreSetData($event);
    }

    /**
     * @dataProvider addAttributesDataProvider
     * @param array $fields
     * @param array $attributes
     * @param int $expectAdds
     */
    public function testOnPreSubmit(array $fields, array $attributes, $expectAdds)
    {
        $attributeFamilyId = 777;
        $entity = $this->getEntityWithFamily();
        $form = $this->getForm();
        $form->expects($this->exactly($expectAdds))
            ->method('add');

        $this->setSecurityValue($this->extension, 'fields', [get_class($entity) => $fields]);

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByFamily')
            ->with($entity->getAttributeFamily())
            ->willReturn($attributes);

        $attributeFamilyRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $attributeFamilyRepository
            ->expects($this->once())
            ->method('find')
            ->with($attributeFamilyId)
            ->willReturn($entity->getAttributeFamily());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(AttributeFamily::class)
            ->willReturn($attributeFamilyRepository);

        $event = new FormEvent($form, ['attributeFamily' => $attributeFamilyId]);
        $this->extension->onPreSubmit($event);
    }

    public function testFinishView()
    {
        $formView = $this->getFormView();
        $form = $this->getForm();

        $this->expectsApplicable();
        $formConfigProvider = $this->getFormConfigProvider();

        $attributeConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [self::DATA_CLASS, 'no_attribute', new Config(
                    $this->getMockBuilder(ConfigIdInterface::class)->getMock(),
                    ['is_attribute' => false]
                )],
                [self::DATA_CLASS, 'attribute',  new Config(
                    $this->getMockBuilder(ConfigIdInterface::class)->getMock(),
                    ['is_attribute' => true]
                )],
            ]);

        $extendConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->getMockBuilder(ConfigInterface::class)->getMock());

        $this->configManager->expects($this->exactly(3))
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['extend', $extendConfigProvider],
                    ['attribute', $attributeConfigProvider],
                    ['form', $formConfigProvider],
                ]
            );

        $this->dynamicFieldsHelper->expects($this->once())
            ->method('shouldBeInitialized')
            ->willReturn(true);

        $this->dynamicFieldsHelper->expects($this->once())
            ->method('addInitialElements');

        $this->extension->finishView($formView, $form, ['data_class' => self::DATA_CLASS, 'enable_attributes' => true]);
    }

    private function expectsApplicable()
    {
        $this->attributeConfigHelper->expects($this->once())
            ->method('isEntityWithAttributes')
            ->with(self::DATA_CLASS)
            ->willReturn(true);
    }

    /**
     * @return ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getViewConfigProvider()
    {
        $viewConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $viewConfigProvider;
    }

    /**
     * @return ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getFormConfigProvider()
    {
        $formConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with(self::DATA_CLASS)
            ->willReturn($this->getFormConfigs());

        return $formConfigProvider;
    }

    /**
     * @return array
     */
    private function getFormConfigs()
    {
        $disabledFormConfig = new Config(
            new FieldConfigId('form', self::DATA_CLASS, 'disabled'),
            ['is_enabled' => false]
        );
        $noAttributeFormConfig = new Config(
            new FieldConfigId('form', self::DATA_CLASS, 'no_attribute'),
            ['is_enabled' => true]
        );
        $attributeFormConfig = new Config(
            new FieldConfigId('form', self::DATA_CLASS, 'attribute'),
            ['is_enabled' => true]
        );

        return [
            $disabledFormConfig,
            $noAttributeFormConfig,
            $attributeFormConfig
        ];
    }

    /**
     * @return FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getForm()
    {
        $form = $this->getMockBuilder(FormInterface::class)
            ->getMock();
        $config = $this->getMockBuilder(FormConfigInterface::class)
            ->getMock();
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
        $config->expects($this->any())
            ->method('getOption')
            ->with('data_class')
            ->willReturn(self::DATA_CLASS);

        return $form;
    }

    /**
     * @return FormView|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getFormView()
    {
        $formView = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $formView;
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    private function setSecurityValue($object, $property, $value)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }

    /**
     * @return AttributeFamilyAwareInterface
     */
    private function getEntityWithFamily()
    {
        return (new TestActivityTarget())->setAttributeFamily(new AttributeFamily());
    }
}

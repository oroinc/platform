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
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DynamicAttributesExtensionTest extends TypeTestCase
{
    private const DATA_CLASS = TestActivityTarget::class;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    /** @var AttributeConfigHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeConfigHelper;

    /** @var DynamicFieldsHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $dynamicFieldsHelper;

    /** @var DynamicAttributesExtension */
    private $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->attributeConfigHelper = $this->createMock(AttributeConfigHelper::class);
        $this->dynamicFieldsHelper = $this->createMock(DynamicFieldsHelper::class);

        $container = TestContainerBuilder::create()
            ->add('oro_entity_config.manager.attribute_manager', $this->attributeManager)
            ->add('oro_entity_config.config.attributes_config_helper', $this->attributeConfigHelper)
            ->add('oro_entity_extend.form.extension.dynamic_fields_helper', $this->dynamicFieldsHelper)
            ->getContainer($this);

        $this->extension = new DynamicAttributesExtension(
            $this->configManager,
            $this->doctrineHelper,
            $container
        );
    }

    public function notApplicableDataProvider(): array
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
     */
    public function testBuildFormWhenNotApplicable(array $options)
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, $options);
    }

    public function testBuildForm()
    {
        $viewConfigProvider = $this->createMock(ConfigProvider::class);
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

        $this->attributeConfigHelper->expects($this->exactly(2))
            ->method('isFieldAttribute')
            ->willReturnOnConsecutiveCalls(false, true);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [FormEvents::PRE_SET_DATA, [$this->extension, 'onPreSetData'], 0],
                [FormEvents::PRE_SUBMIT, [$this->extension, 'onPreSubmit'], 0]
            );

        $this->extension->buildForm($builder, ['data_class' => self::DATA_CLASS, 'enable_attributes' => true]);
    }

    public function preSetDataProviderNoAdd(): array
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
     */
    public function testOnPreSetDataNoAdd(?TestActivityTarget $entity)
    {
        $form = $this->getForm();
        $form->expects($this->never())
            ->method('add');
        $this->attributeManager->expects($this->never())
            ->method('getAttributesByFamily');

        $event = new FormEvent($form, $entity);
        $this->extension->onPreSetData($event);
    }

    public function preSubmitProviderNoAdd(): array
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

    public function addAttributesDataProvider(): array
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
     */
    public function testOnPreSetData(array $fields, array $attributes, int $expectAdds)
    {
        $entity = $this->getEntityWithFamily();
        $form = $this->getForm();

        ReflectionUtil::setPropertyValue($this->extension, 'fields', [get_class($entity) => $fields]);

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
     */
    public function testOnPreSubmit(array $fields, array $attributes, int $expectAdds)
    {
        $attributeFamilyId = 777;
        $entity = $this->getEntityWithFamily();
        $form = $this->getForm();
        $form->expects($this->exactly($expectAdds))
            ->method('add');

        ReflectionUtil::setPropertyValue($this->extension, 'fields', [get_class($entity) => $fields]);

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByFamily')
            ->with($entity->getAttributeFamily())
            ->willReturn($attributes);

        $attributeFamilyRepository = $this->createMock(EntityRepository::class);
        $attributeFamilyRepository->expects($this->once())
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
        $formView = $this->createMock(FormView::class);
        $form = $this->getForm();

        $this->expectsApplicable();
        $formConfigProvider = $this->getFormConfigProvider();

        $attributeConfigProvider = $this->createMock(ConfigProvider::class);
        $attributeConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [self::DATA_CLASS, 'no_attribute', new Config(
                    $this->createMock(ConfigIdInterface::class),
                    ['is_attribute' => false]
                )],
                [self::DATA_CLASS, 'attribute',  new Config(
                    $this->createMock(ConfigIdInterface::class),
                    ['is_attribute' => true]
                )],
            ]);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->createMock(ConfigInterface::class));

        $this->configManager->expects($this->exactly(3))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['attribute', $attributeConfigProvider],
                ['form', $formConfigProvider],
            ]);

        $this->dynamicFieldsHelper->expects($this->once())
            ->method('shouldBeInitialized')
            ->willReturn(true);

        $this->dynamicFieldsHelper->expects($this->once())
            ->method('addInitialElements');

        $this->extension->finishView($formView, $form, ['data_class' => self::DATA_CLASS, 'enable_attributes' => true]);
    }

    private function expectsApplicable(): void
    {
        $this->attributeConfigHelper->expects($this->once())
            ->method('isEntityWithAttributes')
            ->with(self::DATA_CLASS)
            ->willReturn(true);
    }

    private function getFormConfigProvider(): ConfigProvider
    {
        $formConfigProvider = $this->createMock(ConfigProvider::class);
        $formConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with(self::DATA_CLASS)
            ->willReturn($this->getFormConfigs());

        return $formConfigProvider;
    }

    private function getFormConfigs(): array
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

    private function getForm(): FormInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $form = $this->createMock(FormInterface::class);
        $config = $this->createMock(FormConfigInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
        $config->expects($this->any())
            ->method('getOption')
            ->with('data_class')
            ->willReturn(self::DATA_CLASS);

        return $form;
    }

    private function getEntityWithFamily(): AttributeFamilyAwareInterface
    {
        return (new TestActivityTarget())->setAttributeFamily(new AttributeFamily());
    }
}

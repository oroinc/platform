<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EntityExtendEvents;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DynamicFieldsExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var DynamicFieldsExtension */
    protected $extension;

    /** @var ConfigProviderMock */
    protected $extendConfigProvider;

    /** @var ConfigProviderMock */
    protected $entityConfigProvider;

    /** @var ConfigProviderMock */
    protected $viewConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $fieldTypeHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $dispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FeatureChecker */
    protected $featureChecker;

    protected function setUp()
    {
        $configManager = $this->createMock(ConfigManager::class);
        $this->extendConfigProvider = new ConfigProviderMock($configManager, 'extend');
        $this->entityConfigProvider = new ConfigProviderMock($configManager, 'entity');
        $this->viewConfigProvider = new ConfigProviderMock($configManager, 'view');
        $this->fieldTypeHelper = $this->createMock(FieldTypeHelper::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $propertyAccessor = new PropertyAccessor();
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $container = self::getContainerBuilder()
            ->add('oro_entity_config.provider.extend', $this->extendConfigProvider)
            ->add('oro_entity_config.provider.entity', $this->entityConfigProvider)
            ->add('oro_entity_config.provider.view', $this->viewConfigProvider)
            ->add('oro_entity_extend.extend.field_type_helper', $this->fieldTypeHelper)
            ->add('property_accessor', $propertyAccessor)
            ->add('event_dispatcher', $this->dispatcher)
            ->add('security.authorization_checker', $this->authorizationChecker)
            ->add('oro_featuretoggle.checker.feature_checker', $this->featureChecker)
            ->getContainer($this);

        $this->extension = new DynamicFieldsExtension($container);
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return FieldConfigModel
     */
    protected function getFieldConfigModel($entityClass, $fieldName)
    {
        $entityModel = new EntityConfigModel($entityClass);
        $fieldModel = new FieldConfigModel($fieldName);
        $fieldModel->setEntity($entityModel);

        return $fieldModel;
    }

    public function testGetFieldWhenAccessDenied()
    {
        $entity = new TestProduct();
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $field = $this->getFieldConfigModel($entityClass, $fieldName);

        $this->extendConfigProvider->addFieldConfig($entityClass, $fieldName);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $fieldName))
            ->willReturn(false);

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_field', [$entity, $field])
        );
    }

    public function testGetFieldWhenFieldInvisible()
    {
        $entity = new TestProduct();
        $entity->setName('test');
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $field = $this->getFieldConfigModel($entityClass, $fieldName);

        $this->extendConfigProvider->addFieldConfig($entityClass, $fieldName);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $fieldName))
            ->willReturn(true);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                EntityExtendEvents::BEFORE_VALUE_RENDER,
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName)
                )
            )
            ->willReturnCallback(function ($eventName, ValueRenderEvent $event) {
                $event->setFieldVisibility(false);
            });

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_field', [$entity, $field])
        );
    }

    public function testGetFieldWhenFieldValueIsChangedByListener()
    {
        $entity = new TestProduct();
        $entity->setName('test');
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';
        $field = $this->getFieldConfigModel($entityClass, $fieldName);

        $this->extendConfigProvider->addFieldConfig($entityClass, $fieldName, $fieldType);
        $this->entityConfigProvider->addFieldConfig($entityClass, $fieldName, $fieldType);
        $this->viewConfigProvider->addFieldConfig($entityClass, $fieldName, $fieldType);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $fieldName))
            ->willReturn(true);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                EntityExtendEvents::BEFORE_VALUE_RENDER,
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                )
            )
            ->willReturnCallback(function ($eventName, ValueRenderEvent $event) {
                $event->setFieldViewValue('new value');
            });

        self::assertSame(
            [
                'type'  => $fieldType,
                'label' => $fieldName,
                'value' => 'new value',
            ],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_field', [$entity, $field])
        );
    }

    public function testGetFieldWhenNoLabelAndViewType()
    {
        $entity = new TestProduct();
        $entity->setName('test');
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';
        $field = $this->getFieldConfigModel($entityClass, $fieldName);

        $this->extendConfigProvider->addFieldConfig($entityClass, $fieldName, $fieldType);
        $this->entityConfigProvider->addFieldConfig($entityClass, $fieldName, $fieldType);
        $this->viewConfigProvider->addFieldConfig($entityClass, $fieldName, $fieldType);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $fieldName))
            ->willReturn(true);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                EntityExtendEvents::BEFORE_VALUE_RENDER,
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                )
            );

        self::assertSame(
            [
                'type'  => $fieldType,
                'label' => $fieldName,
                'value' => $entity->getName(),
            ],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_field', [$entity, $field])
        );
    }

    public function testGetFieldWithLabelAndViewType()
    {
        $entity = new TestProduct();
        $entity->setName('test');
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';
        $field = $this->getFieldConfigModel($entityClass, $fieldName);

        $this->extendConfigProvider->addFieldConfig($entityClass, $fieldName, $fieldType);
        $this->entityConfigProvider->addFieldConfig($entityClass, $fieldName, $fieldType, ['label' => 'field.label']);
        $this->viewConfigProvider->addFieldConfig($entityClass, $fieldName, $fieldType, ['type' => 'view.type']);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $fieldName))
            ->willReturn(true);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                EntityExtendEvents::BEFORE_VALUE_RENDER,
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                )
            );

        self::assertSame(
            [
                'type'  => 'view.type',
                'label' => 'field.label',
                'value' => $entity->getName(),
            ],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_field', [$entity, $field])
        );
    }

    public function testGetFieldsForSystemField()
    {
        $entity = new TestProduct();
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['owner' => ExtendScope::OWNER_SYSTEM]
        );

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsForNotAccessibleField()
    {
        $entity = new TestProduct();
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['owner' => ExtendScope::OWNER_CUSTOM, 'is_extend' => true, 'is_deleted' => true]
        );

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsForNotDisplayableField()
    {
        $entity = new TestProduct();
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['owner' => ExtendScope::OWNER_CUSTOM]
        );
        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['is_displayable' => false]
        );

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsForNotAccessibleRelation()
    {
        $entity = new TestProduct();
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['owner' => ExtendScope::OWNER_CUSTOM, 'target_entity' => 'Test\TargetEntity']
        );
        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['is_displayable' => true]
        );

        $this->extendConfigProvider->addEntityConfig(
            'Test\TargetEntity',
            ['is_extend' => true, 'is_deleted' => true]
        );

        $this->fieldTypeHelper->expects(self::once())
            ->method('getUnderlyingType')
            ->with($fieldType)
            ->willReturn(RelationType::MANY_TO_ONE);
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsForAccessibleRelation()
    {
        $entity = new TestProduct();
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['owner' => ExtendScope::OWNER_CUSTOM, 'target_entity' => 'Test\TargetEntity']
        );
        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['is_displayable' => true]
        );

        $this->extendConfigProvider->addEntityConfig('Test\TargetEntity', []);

        $this->fieldTypeHelper->expects(self::once())
            ->method('getUnderlyingType')
            ->with($fieldType)
            ->willReturn(RelationType::MANY_TO_ONE);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $fieldName))
            ->willReturn(false);

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsWhenAccessDenied()
    {
        $entity = new TestProduct();
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['owner' => ExtendScope::OWNER_CUSTOM]
        );
        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['is_displayable' => true]
        );

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $fieldName))
            ->willReturn(false);

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsForInvisibleField()
    {
        $entity = new TestProduct();
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['owner' => ExtendScope::OWNER_CUSTOM]
        );
        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['is_displayable' => true]
        );

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $fieldName))
            ->willReturn(true);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                EntityExtendEvents::BEFORE_VALUE_RENDER,
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                )
            )
            ->willReturnCallback(function ($eventName, ValueRenderEvent $event) {
                $event->setFieldVisibility(false);
            });

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsWhenFieldValueIsChangedByListener()
    {
        $entity = new TestProduct();
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['owner' => ExtendScope::OWNER_CUSTOM]
        );
        $this->entityConfigProvider->addFieldConfig($entityClass, $fieldName, $fieldType);
        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['is_displayable' => true]
        );

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $fieldName))
            ->willReturn(true);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                EntityExtendEvents::BEFORE_VALUE_RENDER,
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                )
            )
            ->willReturnCallback(function ($eventName, ValueRenderEvent $event) {
                $event->setFieldViewValue('new value');
            });

        self::assertSame(
            [
                $fieldName => [
                    'type'  => $fieldType,
                    'label' => $fieldName,
                    'value' => 'new value',
                ]
            ],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsWhenNoLabelAndViewType()
    {
        $entity = new TestProduct();
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['owner' => ExtendScope::OWNER_CUSTOM]
        );
        $this->entityConfigProvider->addFieldConfig($entityClass, $fieldName, $fieldType);
        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['is_displayable' => true]
        );

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $fieldName))
            ->willReturn(true);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                EntityExtendEvents::BEFORE_VALUE_RENDER,
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                )
            );

        self::assertSame(
            [
                $fieldName => [
                    'type'  => $fieldType,
                    'label' => $fieldName,
                    'value' => $entity->getName(),
                ]
            ],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsWithLabelAndViewType()
    {
        $entity = new TestProduct();
        $entity->setName('test');
        $entityClass = TestProduct::class;
        $fieldName = 'name';
        $fieldType = 'string';

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['owner' => ExtendScope::OWNER_CUSTOM]
        );
        $this->entityConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['label' => 'field.label']
        );
        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            $fieldName,
            $fieldType,
            ['is_displayable' => true, 'type' => 'view.type']
        );

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('VIEW', new FieldVote($entity, $fieldName))
            ->willReturn(true);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                EntityExtendEvents::BEFORE_VALUE_RENDER,
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                )
            );

        self::assertSame(
            [
                $fieldName => [
                    'type'  => 'view.type',
                    'label' => 'field.label',
                    'value' => $entity->getName(),
                ]
            ],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsShouldBeInOriginalOrderIfNoPriority()
    {
        $entity = new TestProduct();
        $entity->setId(123);
        $entity->setName('test');
        $entityClass = TestProduct::class;

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            'id',
            'integer',
            ['owner' => ExtendScope::OWNER_CUSTOM]
        );
        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            'name',
            'string',
            ['owner' => ExtendScope::OWNER_CUSTOM]
        );

        $this->entityConfigProvider->addFieldConfig(
            $entityClass,
            'id',
            'integer',
            ['label' => 'id.label']
        );
        $this->entityConfigProvider->addFieldConfig(
            $entityClass,
            'name',
            'string',
            ['label' => 'name.label']
        );

        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            'id',
            'string',
            ['is_displayable' => true]
        );
        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            'name',
            'string',
            ['is_displayable' => true]
        );

        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->willReturn(true);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->with(EntityExtendEvents::BEFORE_VALUE_RENDER);

        self::assertSame(
            [
                'id'   => [
                    'type'  => 'integer',
                    'label' => 'id.label',
                    'value' => $entity->getId(),
                ],
                'name' => [
                    'type'  => 'string',
                    'label' => 'name.label',
                    'value' => $entity->getName(),
                ],
            ],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsShouldBeSortedByPriority()
    {
        $entity = new TestProduct();
        $entity->setId(123);
        $entity->setName('test');
        $entityClass = TestProduct::class;

        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            'id',
            'integer',
            ['owner' => ExtendScope::OWNER_CUSTOM]
        );
        $this->extendConfigProvider->addFieldConfig(
            $entityClass,
            'name',
            'string',
            ['owner' => ExtendScope::OWNER_CUSTOM]
        );

        $this->entityConfigProvider->addFieldConfig(
            $entityClass,
            'id',
            'integer',
            ['label' => 'id.label']
        );
        $this->entityConfigProvider->addFieldConfig(
            $entityClass,
            'name',
            'string',
            ['label' => 'name.label']
        );

        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            'id',
            'string',
            ['is_displayable' => true, 'priority' => 10]
        );
        $this->viewConfigProvider->addFieldConfig(
            $entityClass,
            'name',
            'string',
            ['is_displayable' => true, 'priority' => 20]
        );

        $this->authorizationChecker->expects(self::exactly(2))
            ->method('isGranted')
            ->willReturn(true);
        $this->dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->with(EntityExtendEvents::BEFORE_VALUE_RENDER);

        self::assertSame(
            [
                'name' => [
                    'type'  => 'string',
                    'label' => 'name.label',
                    'value' => $entity->getName(),
                ],
                'id'   => [
                    'type'  => 'integer',
                    'label' => 'id.label',
                    'value' => $entity->getId(),
                ],
            ],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }
}

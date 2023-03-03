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
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DynamicFieldsExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ConfigProviderMock */
    private $extendConfigProvider;

    /** @var ConfigProviderMock */
    private $entityConfigProvider;

    /** @var ConfigProviderMock */
    private $viewConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $fieldTypeHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FeatureChecker */
    private $featureChecker;

    /** @var DynamicFieldsExtension */
    private $extension;

    protected function setUp(): void
    {
        $configManager = $this->createMock(ConfigManager::class);
        $this->extendConfigProvider = new ConfigProviderMock($configManager, 'extend');
        $this->entityConfigProvider = new ConfigProviderMock($configManager, 'entity');
        $this->viewConfigProvider = new ConfigProviderMock($configManager, 'view');
        $this->fieldTypeHelper = $this->createMock(FieldTypeHelper::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $container = self::getContainerBuilder()
            ->add('oro_entity_config.provider.extend', $this->extendConfigProvider)
            ->add('oro_entity_config.provider.entity', $this->entityConfigProvider)
            ->add('oro_entity_config.provider.view', $this->viewConfigProvider)
            ->add('oro_entity_extend.extend.field_type_helper', $this->fieldTypeHelper)
            ->add(PropertyAccessorInterface::class, $propertyAccessor)
            ->add(EventDispatcherInterface::class, $this->dispatcher)
            ->add(AuthorizationCheckerInterface::class, $this->authorizationChecker)
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
    private function getFieldConfigModel($entityClass, $fieldName)
    {
        $entityModel = new EntityConfigModel($entityClass);
        $fieldModel = new FieldConfigModel($fieldName);
        $fieldModel->setEntity($entityModel);

        return $fieldModel;
    }

    public function testGetFieldWhenAccessDenied(): void
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

    public function testGetFieldWhenFieldInvisible(): void
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
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName)
                ),
                EntityExtendEvents::BEFORE_VALUE_RENDER
            )
            ->willReturnCallback(function (ValueRenderEvent $event, $eventName) {
                $event->setFieldVisibility(false);

                return $event;
            });

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_field', [$entity, $field])
        );
    }

    public function testGetFieldWhenFieldValueIsChangedByListener(): void
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
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                ),
                EntityExtendEvents::BEFORE_VALUE_RENDER
            )
            ->willReturnCallback(function (ValueRenderEvent $event, $eventName) {
                $event->setFieldViewValue('new value');

                return $event;
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

    public function testGetFieldWhenNoLabelAndViewType(): void
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
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                ),
                EntityExtendEvents::BEFORE_VALUE_RENDER
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

    public function testGetFieldWithLabelAndViewType(): void
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
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                ),
                EntityExtendEvents::BEFORE_VALUE_RENDER
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

    public function testGetFieldsForSystemField(): void
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

    public function testGetFieldsForNotAccessibleField(): void
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

    public function testGetFieldsForNotDisplayableField(): void
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

    public function testGetFieldsForNotAccessibleRelation(): void
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

    public function testGetFieldsForAccessibleRelation(): void
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

    public function testGetFieldsWhenAccessDenied(): void
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

    public function testGetFieldsForInvisibleField(): void
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
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                ),
                EntityExtendEvents::BEFORE_VALUE_RENDER
            )
            ->willReturnCallback(function (ValueRenderEvent $event, $eventName) {
                $event->setFieldVisibility(false);

                return $event;
            });

        self::assertSame(
            [],
            self::callTwigFunction($this->extension, 'oro_get_dynamic_fields', [$entity])
        );
    }

    public function testGetFieldsWhenFieldValueIsChangedByListener(): void
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
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                ),
                EntityExtendEvents::BEFORE_VALUE_RENDER
            )
            ->willReturnCallback(function (ValueRenderEvent $event, $eventName) {
                $event->setFieldViewValue('new value');

                return $event;
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

    public function testGetFieldsWhenNoLabelAndViewType(): void
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
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                ),
                EntityExtendEvents::BEFORE_VALUE_RENDER
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

    public function testGetFieldsWithLabelAndViewType(): void
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
                new ValueRenderEvent(
                    $entity,
                    $entity->getName(),
                    new FieldConfigId('extend', $entityClass, $fieldName, $fieldType)
                ),
                EntityExtendEvents::BEFORE_VALUE_RENDER
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

    public function testGetFieldsShouldBeInOriginalOrderIfNoPriority(): void
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
            ->with(self::anything(), EntityExtendEvents::BEFORE_VALUE_RENDER);

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

    public function testGetFieldsShouldBeSortedByPriority(): void
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
            ->with(self::anything(), EntityExtendEvents::BEFORE_VALUE_RENDER);

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

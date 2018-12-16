<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\ExcludeCustomFields;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category as TestEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ExcludeCustomFieldsTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var ExcludeCustomFields */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->processor = new ExcludeCustomFields($this->doctrineHelper, $this->configManager);
        $this->context->setClassName(TestEntity::class);
    }

    /**
     * @param string $entityClass
     * @param string $owner
     */
    private function expectExtendedEntity($entityClass, $owner = ExtendScope::OWNER_SYSTEM)
    {
        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', $entityClass)
            ->willReturn(
                new Config(
                    new EntityConfigId('extend', $entityClass),
                    ['is_extend' => true, 'owner' => $owner]
                )
            );
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     * @param array  $config
     */
    private function expectFieldConfig($entityClass, $fieldName, array $config)
    {
        $fieldConfigs = [
            new Config(
                new FieldConfigId('extend', $entityClass, $fieldName),
                $config
            )
        ];
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('extend', $entityClass)
            ->willReturn($fieldConfigs);
    }

    public function testWhenExclusionPolicyIsNotEqualToCustomFields()
    {
        $this->context->setResult($this->createConfigObject([]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [],
            $this->context->getResult()
        );
    }

    public function testForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => []
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(TestEntity::class)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            ['exclusion_policy' => 'custom_fields'],
            $this->context->getResult()
        );
    }

    public function testForNonConfigurableEntity()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => []
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(TestEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(TestEntity::class)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            ['exclusion_policy' => 'custom_fields'],
            $this->context->getResult()
        );
    }

    public function testForConfigurableButNotExtendableEntity()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => []
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(TestEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(TestEntity::class)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', TestEntity::class)
            ->willReturn(
                new Config(
                    new EntityConfigId('extend', TestEntity::class),
                    ['is_extend' => false]
                )
            );
        $this->configManager->expects(self::never())
            ->method('getConfigs');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            ['exclusion_policy' => 'custom_fields'],
            $this->context->getResult()
        );
    }

    public function testForCustomEntity()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => []
        ];

        $this->expectExtendedEntity(TestEntity::class, ExtendScope::OWNER_CUSTOM);
        $this->configManager->expects(self::never())
            ->method('getConfigs');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            ['exclusion_policy' => 'custom_fields'],
            $this->context->getResult()
        );
    }

    public function testForExtendedEntityWhenCustomFieldDoesNotExistInApiConfig()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => []
        ];

        $this->expectExtendedEntity(TestEntity::class);
        $this->expectFieldConfig(
            TestEntity::class,
            'name',
            ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
        );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'custom_fields',
                'fields'           => [
                    'name' => ['exclude' => true]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForExtendedEntityWhenCustomFieldDoesExistInApiConfig()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => [
                'name' => null
            ]
        ];

        $this->expectExtendedEntity(TestEntity::class);
        $this->expectFieldConfig(
            TestEntity::class,
            'name',
            ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
        );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'custom_fields',
                'fields'           => [
                    'name' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForExtendedEntityForRenamedCustomField()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => [
                'renamedName' => [
                    'property_path' => 'name'
                ]
            ]
        ];

        $this->expectExtendedEntity(TestEntity::class);
        $this->expectFieldConfig(
            TestEntity::class,
            'name',
            ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
        );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'custom_fields',
                'fields'           => [
                    'renamedName' => [
                        'property_path' => 'name'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForExtendedEntityWhenCustomFieldExistInApiConfigAndNotExcluded()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => [
                'name' => ['exclude' => false]
            ]
        ];

        $this->expectExtendedEntity(TestEntity::class);
        $this->expectFieldConfig(
            TestEntity::class,
            'name',
            ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
        );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'custom_fields',
                'fields'           => [
                    'name' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForExtendedEntityWhenCustomFieldExistInApiConfigAndExcluded()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => [
                'name' => ['exclude' => true]
            ]
        ];

        $this->expectExtendedEntity(TestEntity::class);
        $this->expectFieldConfig(
            TestEntity::class,
            'name',
            ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
        );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'custom_fields',
                'fields'           => [
                    'name' => ['exclude' => true]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testForExtendedEntityForSystemExtendedField()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => []
        ];

        $this->expectExtendedEntity(TestEntity::class);
        $this->expectFieldConfig(
            TestEntity::class,
            'name',
            ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM]
        );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            ['exclusion_policy' => 'custom_fields'],
            $this->context->getResult()
        );
    }

    public function testForExtendedEntityForNotExtendedField()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => []
        ];

        $this->expectExtendedEntity(TestEntity::class);
        $this->expectFieldConfig(
            TestEntity::class,
            'name',
            ['is_extend' => false, 'owner' => ExtendScope::OWNER_CUSTOM]
        );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            ['exclusion_policy' => 'custom_fields'],
            $this->context->getResult()
        );
    }

    public function testForExtendedEntityForNotAccessibleCustomield()
    {
        $config = [
            'exclusion_policy' => 'custom_fields',
            'fields'           => []
        ];

        $this->expectExtendedEntity(TestEntity::class);
        $this->expectFieldConfig(
            TestEntity::class,
            'name',
            ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM, 'state' => ExtendScope::STATE_NEW]
        );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            ['exclusion_policy' => 'custom_fields'],
            $this->context->getResult()
        );
    }
}

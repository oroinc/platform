<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;

class EntityWithFieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityProvider;

    /** @var EntityFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EntityWithFieldsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->fieldProvider = $this->createMock(EntityFieldProvider::class);
        $this->entityProvider = $this->createMock(EntityProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new EntityWithFieldsProvider(
            $this->fieldProvider,
            $this->entityProvider,
            $this->configManager
        );
    }

    public function testGetFields(): void
    {
        $className          = 'Test\Entity';
        $withVirtualFields  = true;
        $withUnidirectional = true;
        $withRelations      = true;
        $applyExclusions    = true;
        $translate          = true;

        $entities = [
            [
                'name'         => $className,
                'label'        => 'Item',
                'plural_label' => 'Items'
            ],
        ];
        $fields   = [
            [
                'name'  => 'field1',
                'type'  => 'string',
                'label' => 'Field 1'
            ],
        ];

        $this->entityProvider->expects(self::once())
            ->method('getEntities')
            ->with(true, $applyExclusions)
            ->willReturn($entities);
        $this->fieldProvider->expects(self::once())
            ->method('getEntityFields')
            ->with(
                $className,
                EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS
                | EntityFieldProvider::OPTION_WITH_UNIDIRECTIONAL
                | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
                | EntityFieldProvider::OPTION_TRANSLATE
            )
            ->willReturn($fields);

        $result = $this->provider->getFields(
            $withVirtualFields,
            $withUnidirectional,
            $withRelations,
            $applyExclusions,
            $translate
        );

        self::assertEquals(
            [
                $className => [
                    'name'         => $className,
                    'label'        => 'Item',
                    'plural_label' => 'Items',
                    'fields'       => [
                        [
                            'name'  => 'field1',
                            'type'  => 'string',
                            'label' => 'Field 1'
                        ],
                    ]
                ]
            ],
            $result
        );
    }

    public function testGetFieldsWithRoutes(): void
    {
        $className = 'Test\Entity';

        $this->entityProvider->expects(self::once())
            ->method('getEntities')
            ->with(true, true, true)
            ->willReturn([['name' => $className]]);

        $this->fieldProvider->expects(self::once())
            ->method('getEntityFields')
            ->with(
                $className,
                EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
                | EntityFieldProvider::OPTION_TRANSLATE
            )
            ->willReturn(['field1' => []]);

        $entityMetadata = new EntityMetadata(\stdClass::class);
        $entityMetadata->routes = ['routeName' => 'routeValue'];
        $this->configManager->expects(self::once())
            ->method('getEntityMetadata')
            ->with($className)
            ->willReturn($entityMetadata);

        self::assertEquals(
            [
                $className => [
                    'name' => $className,
                    'fields' => [
                        'field1' => [],
                    ],
                    'routes' => [
                        'routeName' => 'routeValue',
                    ],
                ],
            ],
            $this->provider->getFields(false, false, true, true, true, true)
        );
    }

    public function testGetFieldsForEntity(): void
    {
        $className = 'Test\Entity';
        $withVirtualFields = true;
        $withUnidirectional = true;
        $withRelations = true;
        $applyExclusions = true;
        $translate = true;

        $entity = [
            'name'         => $className,
            'label'        => 'Item',
            'plural_label' => 'Items'
        ];
        $fields = [
            [
                'name'  => 'field1',
                'type'  => 'string',
                'label' => 'Field 1'
            ]
        ];

        $this->entityProvider->expects(self::once())
            ->method('getEnabledEntity')
            ->with($className, $applyExclusions, $translate)
            ->willReturn($entity);
        $this->fieldProvider->expects(self::once())
            ->method('getEntityFields')
            ->with(
                $className,
                EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS
                | EntityFieldProvider::OPTION_WITH_UNIDIRECTIONAL
                | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
                | EntityFieldProvider::OPTION_TRANSLATE
            )
            ->willReturn($fields);

        $result = $this->provider->getFieldsForEntity(
            $className,
            $withVirtualFields,
            $withUnidirectional,
            $withRelations,
            $applyExclusions,
            $translate
        );

        self::assertEquals(
            [
                'name'         => $className,
                'label'        => 'Item',
                'plural_label' => 'Items',
                'fields'       => [
                    [
                        'name'  => 'field1',
                        'type'  => 'string',
                        'label' => 'Field 1'
                    ]
                ]
            ],
            $result
        );
    }

    public function testGetFieldsForEntityWithRoutes(): void
    {
        $className = 'Test\Entity';

        $this->entityProvider->expects(self::once())
            ->method('getEnabledEntity')
            ->with($className, true, true)
            ->willReturn(['name' => $className]);

        $this->fieldProvider->expects(self::once())
            ->method('getEntityFields')
            ->with(
                $className,
                EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
                | EntityFieldProvider::OPTION_TRANSLATE
            )
            ->willReturn(['field1' => []]);

        $entityMetadata = new EntityMetadata(\stdClass::class);
        $entityMetadata->routes = ['routeName' => 'routeValue'];
        $this->configManager->expects(self::once())
            ->method('getEntityMetadata')
            ->with($className)
            ->willReturn($entityMetadata);

        self::assertEquals(
            [
                'name'   => $className,
                'fields' => [
                    'field1' => []
                ],
                'routes' => [
                    'routeName' => 'routeValue'
                ]
            ],
            $this->provider->getFieldsForEntity($className, false, false, true, true, true, true)
        );
    }

    public function testGetFieldsForEntityWithRoutesWhenEntityMetadataDoesNotExist(): void
    {
        $className = 'Test\Entity';

        $this->entityProvider->expects(self::once())
            ->method('getEnabledEntity')
            ->with($className, true, true)
            ->willReturn(['name' => $className]);

        $this->fieldProvider->expects(self::once())
            ->method('getEntityFields')
            ->with(
                $className,
                EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
                | EntityFieldProvider::OPTION_TRANSLATE
            )
            ->willReturn(['field1' => []]);

        $this->configManager->expects(self::once())
            ->method('getEntityMetadata')
            ->with($className)
            ->willReturn(null);

        self::assertEquals(
            [
                'name'   => $className,
                'fields' => [
                    'field1' => []
                ],
                'routes' => []
            ],
            $this->provider->getFieldsForEntity($className, false, false, true, true, true, true)
        );
    }
}

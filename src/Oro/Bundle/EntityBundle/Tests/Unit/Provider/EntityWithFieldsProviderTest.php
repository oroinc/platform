<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper;

class EntityWithFieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityWithFieldsProvider */
    private $provider;

    /** @var EntityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityProvider;

    /** @var EntityFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldProvider;

    /** @var EntityConfigHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $configHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->fieldProvider = $this->createMock(EntityFieldProvider::class);
        $this->entityProvider = $this->createMock(EntityProvider::class);
        $this->configHelper = $this->createMock(EntityConfigHelper::class);

        $this->provider = new EntityWithFieldsProvider(
            $this->fieldProvider,
            $this->entityProvider,
            $this->configHelper
        );
    }

    public function testGetFields()
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

        $this->entityProvider->expects($this->once())
            ->method('getEntities')
            ->with(true, $applyExclusions)
            ->will($this->returnValue($entities));
        $this->fieldProvider->expects($this->once())
            ->method('getFields')
            ->with(
                $className,
                $withRelations,
                $withVirtualFields,
                false,
                $withUnidirectional,
                $applyExclusions,
                $translate
            )
            ->will($this->returnValue($fields));

        $result = $this->provider->getFields(
            $withVirtualFields,
            $withUnidirectional,
            $withRelations,
            $applyExclusions,
            $translate
        );

        $this->assertEquals(
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

    public function testGetFieldsWithRoutes()
    {
        $this->entityProvider->expects($this->once())
            ->method('getEntities')
            ->with(true, true, true)
            ->willReturn([['name' => 'entity1']]);

        $this->fieldProvider->expects($this->once())
            ->method('getFields')
            ->with('entity1', true, false, false, false, true, true)
            ->willReturn(['field1' => []]);

        $this->configHelper->expects($this->once())
            ->method('getAvailableRoutes')
            ->with('entity1')
            ->willReturn(['routeName' => 'routeValue']);

        $this->assertEquals(
            [
                'entity1' => [
                    'name' => 'entity1',
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

    public function testGetFieldsForEntity()
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

        $this->entityProvider->expects($this->once())
            ->method('getEnabledEntity')
            ->with($className, $applyExclusions, $translate)
            ->willReturn($entity);
        $this->fieldProvider->expects($this->once())
            ->method('getFields')
            ->with(
                $className,
                $withRelations,
                $withVirtualFields,
                false,
                $withUnidirectional,
                $applyExclusions,
                $translate
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

        $this->assertEquals(
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

    public function testGetFieldsForEntityWithRoutes()
    {
        $className = 'Test\Entity';

        $this->entityProvider->expects($this->once())
            ->method('getEnabledEntity')
            ->with($className, true, true)
            ->willReturn(['name' => $className]);

        $this->fieldProvider->expects($this->once())
            ->method('getFields')
            ->with($className, true, false, false, false, true, true)
            ->willReturn(['field1' => []]);

        $this->configHelper->expects($this->once())
            ->method('getAvailableRoutes')
            ->with($className)
            ->willReturn(['routeName' => 'routeValue']);

        $this->assertEquals(
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
}

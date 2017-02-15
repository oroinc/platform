<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigHelper;

class EntityWithFieldsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityWithFieldsProvider */
    private $provider;

    /** @var EntityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $entityProvider;

    /** @var EntityFieldProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $fieldProvider;

    /** @var EntityConfigHelper|\PHPUnit_Framework_MockObject_MockObject */
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
}

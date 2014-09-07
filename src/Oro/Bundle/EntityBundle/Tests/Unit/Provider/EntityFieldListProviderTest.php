<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;

class EntityWithFieldsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityWithFieldsProvider */
    private $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $entityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $fieldProvider;

    protected function setUp()
    {
        $this->fieldProvider  = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new EntityWithFieldsProvider($this->fieldProvider, $this->entityProvider);
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
}

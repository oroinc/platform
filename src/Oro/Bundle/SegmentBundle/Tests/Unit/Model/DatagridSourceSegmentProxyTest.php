<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Model\DatagridSourceSegmentProxy;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;

class DatagridSourceSegmentProxyTest extends SegmentDefinitionTestCase
{
    /**
     * @dataProvider definitionProvider
     *
     * @param mixed $definition
     * @param mixed $expectedDefinition
     * @param null  $expectedException
     */
    public function testProxy($definition, $expectedDefinition, $expectedException = null)
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $segment = $this->getSegment(false, $definition);
        $expectedDefinition = json_encode($expectedDefinition);

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects($this->any())
            ->method('getIdentifier')
            ->willReturn([self::TEST_IDENTIFIER_NAME]);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->with(self::TEST_ENTITY)
            ->willReturn($entityMetadata);
        $proxy = new DatagridSourceSegmentProxy($segment, $em);

        $this->assertEquals($expectedDefinition, $proxy->getDefinition());
        $this->assertEquals($proxy->getEntity(), $segment->getEntity());
    }

    /**
     * @return array
     */
    public function definitionProvider()
    {
        return [
            'should process definition and convert segment restriction to segment filter' => [
                [
                    'columns' => [
                        [
                            'name'    => 'userName',
                            'label'   => 'User name',
                            'func'    => null,
                            'sorting' => null
                        ]
                    ],
                    'filters' => [
                        [
                            'columnName' => 'createdAt',
                            'criterion'  => [
                                'filter' => 'datetime',
                                'data'   => [
                                    'type'  => 4,
                                    'value' => [
                                        'end' => '2014-03-08 09:47:00'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'columns' => [
                        [
                            'name'    => 'userName',
                            'label'   => 'User name',
                            'func'    => null,
                            'sorting' => null
                        ]
                    ],
                    'filters' => [
                        [
                            'columnName' => self::TEST_IDENTIFIER_NAME,
                            'criterion'  => [
                                'filter' => 'segment',
                                'data'   => [
                                    'value' => self::TEST_IDENTIFIER
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'bad definition given, expected exception'                                    => [
                null,
                null,
                InvalidConfigurationException::class
            ]
        ];
    }

    public function testProxyForNewSegment()
    {
        $definition = json_encode($this->getDefaultDefinition());
        $segment = new Segment();
        $segment->setEntity(self::TEST_ENTITY);
        $segment->setDefinition($definition);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->never())
            ->method('getClassMetadata');

        $proxy = new DatagridSourceSegmentProxy($segment, $em);

        $this->assertEquals($definition, $proxy->getDefinition());
        $this->assertEquals($proxy->getEntity(), $segment->getEntity());
    }
}

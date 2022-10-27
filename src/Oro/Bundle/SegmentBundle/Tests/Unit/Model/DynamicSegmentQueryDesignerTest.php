<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Model\DynamicSegmentQueryDesigner;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;

class DynamicSegmentQueryDesignerTest extends SegmentDefinitionTestCase
{
    public function testQueryDesignerForValidDefinition()
    {
        $segment = $this->getSegment([
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
        ]);
        $expectedDefinition = QueryDefinitionUtil::encodeDefinition([
            'columns' => [
                [
                    'name'    => 'userName',
                    'label'   => 'User name',
                    'func'    => null,
                    'sorting' => null
                ],
                [
                    'name'     => self::TEST_IDENTIFIER_NAME,
                    'distinct' => true
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
        ]);

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects($this->any())
            ->method('getIdentifier')
            ->willReturn([self::TEST_IDENTIFIER_NAME]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->with(self::TEST_ENTITY)
            ->willReturn($entityMetadata);

        $queryDesigner = new DynamicSegmentQueryDesigner($segment, $em);

        $this->assertEquals($expectedDefinition, $queryDesigner->getDefinition());
        $this->assertEquals($segment->getEntity(), $queryDesigner->getEntity());
    }

    public function testQueryDesignerForInvalidDefinition()
    {
        $this->expectException(InvalidConfigurationException::class);

        $segment = $this->getSegment();
        $segment->setDefinition('invalid json');

        $em = $this->createMock(EntityManagerInterface::class);

        $queryDesigner = new DynamicSegmentQueryDesigner($segment, $em);

        $queryDesigner->getDefinition();
    }
}

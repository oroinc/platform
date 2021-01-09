<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Model\SegmentDatagridConfigurationQueryDesigner;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;

class SegmentDatagridConfigurationQueryDesignerTest extends SegmentDefinitionTestCase
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

        $queryDesigner = new SegmentDatagridConfigurationQueryDesigner($segment, $em);

        $this->assertEquals($expectedDefinition, $queryDesigner->getDefinition());
        $this->assertEquals($segment->getEntity(), $queryDesigner->getEntity());
    }

    public function testQueryDesignerForInvalidDefinition()
    {
        $this->expectException(InvalidConfigurationException::class);

        $segment = $this->getSegment();
        $segment->setDefinition('invalid json');

        $em = $this->createMock(EntityManagerInterface::class);

        $queryDesigner = new SegmentDatagridConfigurationQueryDesigner($segment, $em);

        $queryDesigner->getDefinition();
    }

    public function testQueryDesignerForNewSegment()
    {
        $definition = QueryDefinitionUtil::encodeDefinition($this->getDefaultDefinition());
        $segment = new Segment();
        $segment->setEntity(self::TEST_ENTITY);
        $segment->setDefinition($definition);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('getClassMetadata');

        $queryDesigner = new SegmentDatagridConfigurationQueryDesigner($segment, $em);

        $this->assertEquals($definition, $queryDesigner->getDefinition());
        $this->assertEquals($segment->getEntity(), $queryDesigner->getEntity());
    }
}

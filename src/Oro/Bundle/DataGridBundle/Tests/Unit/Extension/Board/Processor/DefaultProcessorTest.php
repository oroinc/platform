<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Board\Processor;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Board\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Board\Processor\DefaultProcessor;

class DefaultProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityClassResolver;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $choiceHelper;

    /**
     * @var DefaultProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityClassResolver = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->choiceHelper = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Tools\ChoiceFieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new DefaultProcessor(
            $this->em,
            $this->entityClassResolver,
            $this->choiceHelper
        );
    }

    public function testGetName()
    {
        $this->assertEquals(DefaultProcessor::NAME, $this->processor->getName());
    }

    public function testGetBoardOptions()
    {
        $config = DatagridConfiguration::create([
            'source' => [
                'type'  => 'orm',
                'query' => [
                    'from' => [
                        ['table' => 'Test:Entity', 'alias' => 'rootAlias']
                    ]
                ]
            ]
        ]);
        $this->entityClassResolver->expects($this->once())
            ->method('getEntityClass')
            ->with('Test:Entity')
            ->will($this->returnValue('Test\Entity'));
        $entityMetaData = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetaData->expects($this->once())
            ->method('hasAssociation')
            ->with('group_field')
            ->will($this->returnValue(true));
        $entityMetaData->expects($this->once())
            ->method('getAssociationMapping')
            ->with('group_field')
            ->will($this->returnValue(['type' => ClassMetadata::MANY_TO_ONE]));
        $entityMetaData->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('group_field')
            ->will($this->returnValue('target_entity'));
        $this->em->expects($this->at(0))
            ->method('getClassMetadata')
            ->with('Test\Entity')
            ->will($this->returnValue($entityMetaData));

        $targetMetaData = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $targetMetaData->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));

        $this->em->expects($this->at(1))
            ->method('getClassMetadata')
            ->with('target_entity')
            ->will($this->returnValue($targetMetaData));

        $this->choiceHelper->expects($this->once())
            ->method('guessLabelField')
            ->with($targetMetaData, 'group_field')
            ->will($this->returnValue('label'));
        $choices = [
            'Identification Alignment' => 'identification_alignment',
            'In Progress' => 'in_progress',
            'Lost' => 'lost',
        ];
        $this->choiceHelper->expects($this->once())
            ->method('getChoices')
            ->with('target_entity', 'id', 'label')
            ->will($this->returnValue($choices));

        $boardConfig = [
            Configuration::GROUP_KEY => [
                Configuration::GROUP_PROPERTY_KEY => 'group_field'
            ],
        ];
        $expected = [
            ['ids' => ['identification_alignment', null], 'label' => 'Identification Alignment'],
            ['ids' => ['in_progress'], 'label' => 'In Progress'],
            ['ids' => ['lost'], 'label' => 'Lost'],
        ];
        $this->assertEquals($expected, $this->processor->getBoardOptions($boardConfig, $config));
    }

    public function testProcessDatasourceNotORM()
    {
        $config = DatagridConfiguration::create([]);
        $dataSource = $this->createMock('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface');
        $dataSource->expects($this->never())
            ->method($this->anything());
        $this->processor->processDatasource($dataSource, [], $config);
    }
}

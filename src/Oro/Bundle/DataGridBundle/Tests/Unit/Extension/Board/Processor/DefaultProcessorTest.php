<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Board\Processor;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Board\BoardExtension;
use Oro\Bundle\DataGridBundle\Extension\Board\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Board\Processor\DefaultProcessor;

class DefaultProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $gridConfigurationHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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

        $this->gridConfigurationHelper = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->choiceHelper = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Tools\ChoiceFieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new DefaultProcessor(
            $this->em,
            $this->gridConfigurationHelper,
            $this->choiceHelper
        );
    }

    public function testGetName()
    {
        $this->assertEquals(DefaultProcessor::NAME, $this->processor->getName());
    }

    public function testGetBoardOptions()
    {
        $config = DatagridConfiguration::create([]);
        $this->gridConfigurationHelper->expects($this->once())->method('getEntity')->with($config)->will(
            $this->returnValue('entity_name')
        );
        $entityMetaData = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $entityMetaData->expects($this->once())->method('hasAssociation')->with('group_field')
            ->will($this->returnValue(true));
        $entityMetaData->expects($this->once())->method('getAssociationMapping')->with('group_field')
            ->will($this->returnValue(['type' => ClassMetadata::MANY_TO_ONE]));
        $entityMetaData->expects($this->once())->method('getAssociationTargetClass')->with('group_field')
            ->will($this->returnValue('target_entity'));
        $this->em->expects($this->at(0))->method('getClassMetadata')->with('entity_name')
            ->will($this->returnValue($entityMetaData));

        $targetMetaData = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $targetMetaData->expects($this->once())->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));

        $this->em->expects($this->at(1))->method('getClassMetadata')->with('target_entity')
            ->will($this->returnValue($targetMetaData));

        $this->choiceHelper->expects($this->once())->method('guessLabelField')->with($targetMetaData, 'group_field')
            ->will($this->returnValue('label'));
        $choices = [
            'identification_alignment' => 'Identification Alignment',
            'in_progress' => 'In Progress',
            'lost' => 'Lost'
        ];
        $this->choiceHelper->expects($this->once())->method('getChoices')->with('target_entity', 'id', 'label')
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
        $dataSource = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface');
        $dataSource->expects($this->never())->method('getQueryBuilder');
        $this->processor->processDatasource($dataSource, [], $config);
    }
}

<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Board\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\Board\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Board\Processor\DefaultProcessor;
use Oro\Bundle\DataGridBundle\Tools\ChoiceFieldHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class DefaultProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var ChoiceFieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $choiceHelper;

    /** @var DefaultProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->choiceHelper = $this->createMock(ChoiceFieldHelper::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->processor = new DefaultProcessor(
            $doctrine,
            $this->entityClassResolver,
            $this->choiceHelper
        );
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
        $entityMetaData = $this->createMock(ClassMetadata::class);
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

        $targetMetaData = $this->createMock(ClassMetadata::class);
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
        $dataSource = $this->createMock(DatasourceInterface::class);
        $dataSource->expects($this->never())
            ->method($this->anything());
        $this->processor->processDatasource($dataSource, [], $config);
    }
}

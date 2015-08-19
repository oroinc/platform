<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationBuilder;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationProvider;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;

class ReportDatagridConfigurationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ReportDatagridConfigurationProvider
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $functionProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $virtualFieldProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    protected function setUp()
    {
        $this->functionProvider = $this->getMock(
            'Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface'
        );

        $this->virtualFieldProvider = $this->getMock(
            'Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface'
        );

        $this->doctrine = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $builder = new ReportDatagridConfigurationBuilder(
            $this->functionProvider,
            $this->virtualFieldProvider,
            $this->doctrine,
            new DatagridGuesserMock()
        );

        $builder->setConfigManager($this->configManager);

        $this->target = new ReportDatagridConfigurationProvider(
            $builder,
            $this->doctrine
        );
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->target->isApplicable(Report::GRID_PREFIX . '1'));
        $this->assertFalse($this->target->isApplicable('oro_not_report_table_1'));
        $this->assertFalse($this->target->isApplicable('1_oro_report_table_1'));
    }

    public function testIsReportValid()
    {
        $exception = new InvalidConfigurationException();
        $this->doctrine->expects($this->at(0))->method('getRepository')->will($this->throwException($exception));
        $this->assertFalse($this->target->isReportValid(''));
    }

    public function testGetConfigurationDoesNotAddActionIfNoRouteConfigured()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity   = 'Oro\Bundle\AddressBundle\Entity\Address';
        $this->prepareMetadata($entity);
        $this->prepareRepository($entity, ['columns' => ['column' => ['name' => 'street']]]);
        $configuration = $this->target->getConfiguration($gridName);
        $this->assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationDoesNotAddActionIfDefinitionHaveGroupingNotByIdentifier()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity   = 'Oro\Bundle\AddressBundle\Entity\Address';

        $definition = array(
            'columns' => array('column' => array('name'=>'street')),
            'grouping_columns' => array(
                array('name'=>'street')
            )
        );

        $metadata = $this->prepareMetadata($entity);

        //only stub
        $metadata->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue(['id']));

        $this->prepareRepository($entity, $definition);

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata    = $this->getEntityMetadata($expectedViewRoute);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->will($this->returnValue($entityMetadata));

        $configuration = $this->target->getConfiguration($gridName);
        $this->assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationDoesNotAddActionIfDefinitionHaveAggregationFunction()
    {
        $gridName = Report::GRID_PREFIX . '1';
        $entity   = 'Oro\Bundle\AddressBundle\Entity\Address';

        $definition = [
            'columns' => [
                'column' => [
                    'name' => 'street',
                    "func" => [
                        "name"       => "Sum",
                        "group_type" => "aggregates",
                        "group_name" => "number",
                    ]
                ]
            ]
        ];

        $metadata = $this->prepareMetadata($entity);

        //only stub
        $metadata->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue(['id']));

        $this->prepareRepository($entity, $definition);

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata    = $this->getEntityMetadata($expectedViewRoute);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->will($this->returnValue($entityMetadata));

        $configuration = $this->target->getConfiguration($gridName);
        $this->assertEmpty($configuration->offsetGetByPath('[actions]'));
    }

    public function testGetConfigurationAddAction()
    {
        $gridName = Report::GRID_PREFIX . '1';

        $expectedIdName = 'test_id';

        $entity = 'Oro\Bundle\AddressBundle\Entity\Address';

        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata    = $this->getEntityMetadata($expectedViewRoute);

        $metadata = $this->prepareMetadata($entity);
        $metadata->expects($this->once())
            ->method('getIdentifier')
            ->will($this->returnValue([$expectedIdName]));

        $definition = array(
            'columns' => array(
                array(
                    'name' => $expectedIdName
                ),
                array(
                    'name' => 'street',
                    "func" => array(
                        "name"       => "Sum",
                        "group_type" => "aggregates",
                        "group_name" => "number",
                    )
                )
            ),
            'grouping_columns' => array(
                array('name'=>$expectedIdName)
            )
        );

        $this->prepareRepository($entity, $definition);
        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->will($this->returnValue($entityMetadata));
        $configuration = $this->target->getConfiguration($gridName);

        $actualProperties = $configuration->offsetGetByPath('[properties]');
        $this->assertArrayHasKey('view_link', $actualProperties);

        $expectedProperties = [
            $expectedIdName => null,
            'view_link'     => [
                'type'   => 'url',
                'route'  => $expectedViewRoute,
                'params' => [$expectedIdName]
            ]
        ];
        $this->assertEquals($expectedProperties, $actualProperties);

        $selectParts           = $configuration->offsetGetByPath('[source][query][select]');
        $selectIdentifierExist = false;
        foreach ($selectParts as $selectPart) {
            if (strpos($selectPart, ".{$expectedIdName}") !== -1) {
                $selectIdentifierExist = true;
            }
        }
        $this->assertTrue($selectIdentifierExist);

        $expectedActions = [
            'view' => [
                'type'         => 'navigate',
                'label'        => 'oro.report.datagrid.row.action.view',
                'acl_resource' => 'VIEW;entity:Oro\Bundle\AddressBundle\Entity\Address',
                'icon'         => 'eye-open',
                'link'         => 'view_link',
                'rowAction'    => true
            ]
        ];
        $this->assertEquals($expectedActions, $configuration->offsetGetByPath('[actions]'));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareMetadata()
    {
        $manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($manager));

        $metadata = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));

        return $metadata;
    }

    /**
     * Return created report mock
     *
     * @param string $className
     * @param array  $definition
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareRepository($className, array $definition)
    {
        $report     = $this->getMock('Oro\Bundle\ReportBundle\Entity\Report');
        $definition = json_encode($definition);

        //only stub because of calls time depend on DatagridConfigurationBuilder realisation
        $report->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($definition));
        $report->expects($this->exactly(2))
            ->method('getEntity')
            ->will($this->returnValue($className));
        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('find')
            ->will($this->returnValue($report));
        $this->doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        return $report;
    }

    /**
     * @param string $viewRoute
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEntityMetadata($viewRoute)
    {
        $entityMetadata = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $entityMetadata->routeView = $viewRoute;

        return $entityMetadata;
    }
}

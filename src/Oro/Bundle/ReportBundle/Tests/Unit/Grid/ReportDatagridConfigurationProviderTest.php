<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Grid;

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
            'Oro\Bundle\QueryDesignerBundle\QueryDesigner\VirtualFieldProviderInterface'
        );

        $this->doctrine = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->target = new ReportDatagridConfigurationProvider(
            $this->functionProvider,
            $this->virtualFieldProvider,
            $this->doctrine,
            $this->configManager
        );
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->target->isApplicable('oro_report_table_1'));
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
        $gridName = 'oro_report_table_1';
        $entity = 'Oro\Bundle\AddressBundle\Entity\Address';
        $this->prepareMetadata($entity);
        $this->prepareRepository($entity);
        $configuration = $this->target->getConfiguration($gridName);
        $this->assertEmpty($configuration->offsetGetByPath('[action]'));
    }

    public function testGetConfigurationAddAction()
    {
        $gridName = 'oro_report_table_1';

        $expectedIdName = rand();

        $entity = 'Oro\Bundle\AddressBundle\Entity\Address';

        $entityMetadata = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->prepareMetadata($entity);
        $metadata->expects($this->once())
            ->method('getIdentifier')
            ->will($this->returnValue(array($expectedIdName)));
        $expectedViewRoute = 'oro_sample_view';
        $entityMetadata->routeView = $expectedViewRoute;

        $this->prepareRepository($entity);
        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entity)
            ->will($this->returnValue($entityMetadata));
        $configuration = $this->target->getConfiguration($gridName);

        $actualProperties = $configuration->offsetGetByPath('[properties]');
        $this->assertArrayHasKey('view_link', $actualProperties);

        $idGeneratedName = $actualProperties['view_link']['params']['id'];
        $this->assertNotEmpty($idGeneratedName);

        $expectedProperties = array(
            $idGeneratedName => array(),
            'view_link'      => array(
                'type'   => 'url',
                'route'  => $expectedViewRoute,
                'params' => array('id' => $idGeneratedName)
            )
        );
        $this->assertEquals($expectedProperties, $actualProperties);

        $selectParts = $configuration->offsetGetByPath('[source][query][select]');
        $selectIdentifierExist = false;
        foreach ($selectParts as $selectPart) {
            if (strpos($selectPart, ".{$expectedIdName} as {$idGeneratedName}") !== -1) {
                $selectIdentifierExist = true;
            }
        }
        $this->assertTrue($selectIdentifierExist);

        $expectedActions = array(
            'view' => array(
                'type'         => 'navigate',
                'label'        => 'View',
                'icon'         => 'eye-open',
                'link'         => 'view_link',
                'rowAction'    => true
            )
        );
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
     * @param $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareRepository($className)
    {
        $report = $this->getMock('Oro\Bundle\ReportBundle\Entity\Report');
        $definition = json_encode(array('columns' => array('1'=>1)));
        $report->expects($this->once())
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
}

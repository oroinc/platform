<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_DATASOURCE_TYPE = 'array';
    const TEST_DATAGRID_NAME   = 'testGrid';

    const DEFAULT_DATAGRID_CLASS = 'Oro\Bundle\DataGridBundle\Datagrid\Datagrid';
    const DEFAULT_ACCEPTOR_CLASS = 'Oro\Bundle\DataGridBundle\Extension\Acceptor';

    /** @var \PHPUnit_Framework_MockObject_MockObject|Builder */
    protected $builder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher');
        $this->securityFacade  = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();
    }

    protected function tearDown()
    {
        unset($this->eventDispatcher);
        unset($this->securityFacade);
    }

    public function testRegisterExtensions()
    {
        $builder  = $this->getBuilderMock();
        $extMock  = $this->getMockForAbstractClass('Oro\Bundle\DataGridBundle\Extension\ExtensionVisitorInterface');
        $ext2Mock = clone $extMock;
        $ext3Mock = clone $extMock;

        $builder->registerExtension($extMock);
        $builder->registerExtension($ext2Mock);

        $this->assertAttributeContains($extMock, 'extensions', $builder);
        $this->assertAttributeContains($ext2Mock, 'extensions', $builder);
        $this->assertAttributeNotContains($ext3Mock, 'extensions', $builder);
    }

    public function testRegisterDatasource()
    {
        $builder        = $this->getBuilderMock();
        $datasourceMock = $this->getMockForAbstractClass('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface');

        $builder->registerDatasource(self::TEST_DATASOURCE_TYPE, $datasourceMock);

        $this->assertAttributeContains($datasourceMock, 'dataSources', $builder);
        $this->assertAttributeCount(1, 'dataSources', $builder);
    }

    /**
     * @dataProvider buildProvider
     *
     * @param DatagridConfiguration $config
     * @param string                $resultFQCN
     * @param array                 $raisedEvents
     * @param int                   $extensionsCount
     * @param array                 $extensionsMocks
     */
    public function testBuild($config, $resultFQCN, $raisedEvents, $extensionsCount, $extensionsMocks = [])
    {
        $builder = $this->getBuilderMock(['buildDataSource']);
        $parameters = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag');

        foreach ($extensionsMocks as $extension) {
            $builder->registerExtension($extension);
        }

        foreach ($raisedEvents as $at => $eventDetails) {
            list($name, $eventType) = $eventDetails;
            $this->eventDispatcher->expects($this->at($at))->method('dispatch')
                ->with(
                    $this->equalTo($name),
                    $this->callback(
                        function ($event) use ($eventType, $resultFQCN) {
                            $this->isInstanceOf($eventType, $event);
                            $this->isInstanceOf($resultFQCN, $event->getDatagrid());
                            return true;
                        }
                    )
                );
        }

        /** @var DatagridInterface $result */
        $result = $builder->build($config, $parameters);
        $this->assertInstanceOf($resultFQCN, $result);

        $this->assertInstanceOf(self::DEFAULT_ACCEPTOR_CLASS, $result->getAcceptor());

        $this->assertCount($extensionsCount, $result->getAcceptor()->getExtensions());
    }

    /**
     * @return array
     */
    public function buildProvider()
    {
        $stubDatagridClass = 'Oro\Bundle\DataGridBundle\Datagrid\Datagrid';
        $baseEventList     = [
            ['oro_datagrid.datagrid.build.before', 'Oro\Bundle\DataGridBundle\Event\BuildBefore'],
            ['oro_datagrid.datagrid.build.after', 'Oro\Bundle\DataGridBundle\Event\BuildAfter'],
        ];

        $extToAdd    = $this->getMockForAbstractClass('Oro\Bundle\DataGridBundle\Extension\ExtensionVisitorInterface');
        $extToAdd2   = $this->getMockForAbstractClass('Oro\Bundle\DataGridBundle\Extension\ExtensionVisitorInterface');
        $extNotToAdd = $this->getMockForAbstractClass('Oro\Bundle\DataGridBundle\Extension\ExtensionVisitorInterface');

        $extToAdd->expects($this->any())->method('isApplicable')->will($this->returnValue(true));
        $extToAdd2->expects($this->any())->method('isApplicable')->will($this->returnValue(true));
        $extNotToAdd->expects($this->any())->method('isApplicable')->will($this->returnValue(false));

        return [
            'Base datagrid should be created without extensions'         => [
                DatagridConfiguration::createNamed(self::TEST_DATAGRID_NAME, []),
                self::DEFAULT_DATAGRID_CLASS,
                $baseEventList,
                0
            ],
            'Datagrid should be created as object type passed in config' => [
                DatagridConfiguration::createNamed(
                    self::TEST_DATAGRID_NAME,
                    ['options' => ['base_datagrid_class' => $stubDatagridClass]]
                ),
                $stubDatagridClass,
                $baseEventList,
                0
            ],
            'Extension passed check'                                     => [
                DatagridConfiguration::createNamed(self::TEST_DATAGRID_NAME, []),
                self::DEFAULT_DATAGRID_CLASS,
                $baseEventList,
                1,
                [$extToAdd, $extNotToAdd]
            ],
            'Both extensions passed check'                               => [
                DatagridConfiguration::createNamed(self::TEST_DATAGRID_NAME, []),
                self::DEFAULT_DATAGRID_CLASS,
                $baseEventList,
                2,
                [$extToAdd, $extNotToAdd, $extToAdd2]
            ]
        ];
    }

    /**
     * @dataProvider buildDatasourceProvider
     *
     * @param  DatagridConfiguration $config
     * @param array                  $datasources
     * @param array                  $expectedException
     * @param int                    $processCallExpects
     */
    public function testBuildDatasource(
        $config,
        $datasources = [],
        array $expectedException = null,
        $processCallExpects = 0
    ) {
        $builder = $this->getBuilderMock(['isResourceGranted']);
        $grid    = $this->getMockForAbstractClass('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        foreach ($datasources as $type => $obj) {
            $builder->registerDatasource($type, $obj);
            if ($processCallExpects) {
                $obj->expects($this->once())->method('process')->with($grid);
            }
        }

        if ($expectedException !== null) {
            list ($name, $message) = $expectedException;

            $this->setExpectedException($name, $message);
        }

        $method = new \ReflectionMethod($builder, 'buildDataSource');
        $method->setAccessible(true);
        $method->invoke($builder, $grid, $config);
    }

    /**
     * @return array
     */
    public function buildDatasourceProvider()
    {
        $datasourceMock = $this->getMockForAbstractClass('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface');
        return [
            'Datasource not configured, exceptions should be thrown' => [
                DatagridConfiguration::create([]),
                [],
                ['\RuntimeException', 'Datagrid source does not configured']
            ],
            'Configured datasource does not exist'                   => [
                DatagridConfiguration::create(['source' => ['type' => self::TEST_DATASOURCE_TYPE]]),
                [],
                ['\RuntimeException', sprintf('Datagrid source "%s" does not exist', self::TEST_DATASOURCE_TYPE)]
            ],
            'Configured correct and allowed'                         => [
                DatagridConfiguration::create(
                    ['source' => ['type' => self::TEST_DATASOURCE_TYPE]]
                ),
                [self::TEST_DATASOURCE_TYPE => clone $datasourceMock],
                null,
                true
            ]
        ];
    }

    /**
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Builder
     */
    protected function getBuilderMock($methods = ['build'])
    {
        $args = [
            self::DEFAULT_DATAGRID_CLASS,
            self::DEFAULT_ACCEPTOR_CLASS,
            $this->eventDispatcher,
            $this->securityFacade
        ];
        return $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Builder')
            ->setConstructorArgs($args)
            ->setMethods($methods)->getMock();
    }
}

<?php

namespace Oro\Bundle\DataGridBundle\Tests\Twig;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Twig\DataGridExtension;

class DataGridExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $nameStrategy;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var DataGridExtension
     */
    protected $twigExtension;

    protected function setUp()
    {
        $this->manager = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\ManagerInterface');
        $this->nameStrategy = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\NameStrategyInterface');
        $this->router = $this->getMock('Symfony\\Component\\Routing\\RouterInterface');
        $this->securityFacade = $this->getMockBuilder('Oro\\Bundle\\SecurityBundle\\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigExtension = new DataGridExtension(
            $this->manager,
            $this->nameStrategy,
            $this->router,
            $this->securityFacade
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_datagrid', $this->twigExtension->getName());
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            ['oro_datagrid_build', 'getGrid'],
            ['oro_datagrid_data', 'getGridData'],
            ['oro_datagrid_metadata', 'getGridMetadata'],
            ['oro_datagrid_generate_element_id', 'generateGridElementId'],
            ['oro_datagrid_build_fullname', 'buildGridFullName'],
        ];

        $actualFunctions = $this->twigExtension->getFunctions();
        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($actualFunctions as $twigFunction) {
            $expectedFunction = current($expectedFunctions);

            $this->assertInstanceOf('\Twig_SimpleFunction', $twigFunction);
            $this->assertEquals($expectedFunction[0], $twigFunction->getName());
            $this->assertEquals([$this->twigExtension, $expectedFunction[1]], $twigFunction->getCallable());

            next($expectedFunctions);
        }
    }

    public function testGetGridWorks()
    {
        $gridName = 'test-grid';
        $params = ['foo' => 'bar'];

        $grid = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');

        $configuration = $this->getMockBuilder('Oro\\Bundle\\DataGridBundle\\Datagrid\\Common\\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $configuration->expects($this->once())
            ->method('offsetGetByPath')
            ->with(Builder::DATASOURCE_ACL_PATH)
            ->will($this->returnValue(null));

        $this->manager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->will($this->returnValue($configuration));

        $this->manager->expects($this->once())
            ->method('getDatagridByRequestParams')
            ->with($gridName, $params)
            ->will($this->returnValue($grid));

        $this->assertSame($grid, $this->twigExtension->getGrid($gridName, $params));
    }

    public function testGetGridReturnsNullWhenConfigurationNotFound()
    {
        $gridName = 'test-grid';

        $this->manager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->will($this->returnValue(null));

        $this->assertNull($this->twigExtension->getGrid($gridName));
    }

    public function testGetGridReturnsNullWhenDontHavePermissions()
    {
        $gridName = 'test-grid';

        $acl = 'test-acl';

        $configuration = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $configuration->expects($this->once())
            ->method('offsetGetByPath')
            ->with(Builder::DATASOURCE_ACL_PATH)
            ->will($this->returnValue($acl));

        $this->manager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->will($this->returnValue($configuration));

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($acl)
            ->will($this->returnValue(false));

        $this->assertNull($this->twigExtension->getGrid($gridName));
    }

    public function testGetGridMetadataWorks()
    {
        $gridName = 'test-grid';
        $gridScope = 'test-scope';
        $gridFullName = 'test-grid:test-scope';
        $params = ['foo' => 'bar'];
        $url = '/datagrid/test-grid?test-grid-test-scope=foo=bar';

        $grid = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');
        $metadata = $this->getMockBuilder('Oro\\Bundle\\DataGridBundle\\Datagrid\\Common\\MetadataObject')
            ->disableOriginalConstructor()
            ->getMock();

        $grid->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($metadata));

        $grid->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($gridName));

        $grid->expects($this->once())
            ->method('getScope')
            ->will($this->returnValue($gridScope));

        $this->nameStrategy->expects($this->once())
            ->method('buildGridFullName')
            ->with($gridName, $gridScope)
            ->will($this->returnValue($gridFullName));

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                DataGridExtension::ROUTE,
                ['gridName' => $gridFullName, $gridFullName => $params]
            )
            ->will($this->returnValue($url));

        $metadata->expects($this->once())
            ->method('offsetAddToArray')
            ->with('options', ['url' => $url, 'urlParams' => $params]);

        $metadataArray = ['metadata-array'];
        $metadata->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($metadataArray));

        $this->assertSame($metadataArray, $this->twigExtension->getGridMetadata($grid, $params));
    }

    public function testGetGridDataWorks()
    {
        $grid = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');
        $gridData = $this->getMockBuilder('Oro\\Bundle\\DataGridBundle\\Datagrid\\Common\\ResultsObject')
            ->disableOriginalConstructor()
            ->getMock();

        $grid->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($gridData));

        $gridDataArray = ['grid-data'];

        $gridData->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($gridDataArray));

        $this->assertSame($gridDataArray, $this->twigExtension->getGridData($grid));
    }

    /**
     * @dataProvider generateGridElementIdDataProvider
     */
    public function testGenerateGridElementIdWorks($gridName, $gridScope, $expectedPattern)
    {
        $grid = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');

        $grid->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($gridName));

        $grid->expects($this->atLeastOnce())
            ->method('getScope')
            ->will($this->returnValue($gridScope));

        $this->assertRegExp($expectedPattern, $this->twigExtension->generateGridElementId($grid));
    }

    public function generateGridElementIdDataProvider()
    {
        return [
            [
                'test-grid',
                'test-scope',
                '/grid-test-grid-test-scope-[\d]+/'
            ],
            [
                'test-grid',
                '',
                '/grid-test-grid-[\d]+/'
            ]
        ];
    }

    public function testBuildGridFullNameWorks()
    {
        $expectedFullName = 'test-grid:test-scope';
        $gridName = 'test-grid';
        $gridScope = 'test-scope';

        $this->nameStrategy->expects($this->once())
            ->method('buildGridFullName')
            ->will($this->returnValue($expectedFullName));

        $this->assertEquals($expectedFullName, $this->twigExtension->buildGridFullName($gridName, $gridScope));
    }
}

<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Twig;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\DataGridBundle\Twig\DataGridExtension;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DataGridExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerInterface */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|NameStrategyInterface */
    protected $nameStrategy;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface */
    protected $router;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var DataGridExtension */
    protected $twigExtension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridRouteHelper */
    protected $datagridRouteHelper;

    protected function setUp()
    {
        $this->manager = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\ManagerInterface');
        $this->nameStrategy = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\NameStrategyInterface');
        $this->router = $this->getMock('Symfony\\Component\\Routing\\RouterInterface');
        $this->securityFacade = $this->getMockBuilder('Oro\\Bundle\\SecurityBundle\\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->datagridRouteHelper = $this->getMockBuilder(DatagridRouteHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigExtension = new DataGridExtension(
            $this->manager,
            $this->nameStrategy,
            $this->router,
            $this->securityFacade,
            $this->datagridRouteHelper
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_datagrid', $this->twigExtension->getName());
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            ['oro_datagrid_build', [$this->twigExtension, 'getGrid']],
            ['oro_datagrid_data', [$this->twigExtension, 'getGridData']],
            ['oro_datagrid_metadata', [$this->twigExtension, 'getGridMetadata']],
            ['oro_datagrid_generate_element_id', [$this->twigExtension, 'generateGridElementId']],
            ['oro_datagrid_build_fullname', [$this->twigExtension, 'buildGridFullName']],
            ['oro_datagrid_build_inputname', [$this->twigExtension, 'buildGridInputName']],
            ['oro_datagrid_link', [$this->datagridRouteHelper, 'generate']],
        ];
        /** @var \Twig_SimpleFunction[] $actualFunctions */
        $actualFunctions = $this->twigExtension->getFunctions();
        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($actualFunctions as $twigFunction) {
            $expectedFunction = current($expectedFunctions);

            $this->assertInstanceOf('\Twig_SimpleFunction', $twigFunction);
            $this->assertEquals($expectedFunction[0], $twigFunction->getName());
            $this->assertEquals($expectedFunction[1], $twigFunction->getCallable());

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
            ->setMethods(['getAclResource'])
            ->getMock();

        $configuration->expects($this->once())
            ->method('getAclResource')
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
            ->method('getAclResource')
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

    /**
     * @param mixed $configRoute
     * @param string $expectedRoute
     *
     * @dataProvider routeProvider
     */
    public function testGetGridMetadataWorks($configRoute, $expectedRoute)
    {
        $gridName = 'test-grid';
        $gridScope = 'test-scope';
        $gridFullName = 'test-grid:test-scope';
        $params = ['foo' => 'bar'];
        $url = '/datagrid/test-grid?test-grid-test-scope=foo=bar';

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $grid */
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

        $this->nameStrategy->expects($this->once())
            ->method('getGridUniqueName')
            ->with($gridFullName)
            ->will($this->returnValue($gridFullName));

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                $expectedRoute,
                ['gridName' => $gridFullName, $gridFullName => $params]
            )
            ->will($this->returnValue($url));

        $metadata->expects($this->once())
            ->method('offsetAddToArray')
            ->with('options', ['url' => $url, 'urlParams' => $params]);

        $metadata->expects($this->any())
            ->method('offsetGetByPath')
            ->with()
            ->will(
                $this->returnValueMap(
                    [
                        ['[options][route]', $configRoute],
                    ]
                )
            );

        $metadataArray = ['metadata-array'];
        $metadata->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($metadataArray));

        $this->assertSame($metadataArray, $this->twigExtension->getGridMetadata($grid, $params));
    }

    /**
     * @return array
     */
    public function routeProvider()
    {
        return [
            [null, DataGridExtension::ROUTE],
        ];
    }

    public function testGetGridDataWorks()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $grid */
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
     * @param string $gridName
     * @param string $gridScope
     * @param string $expectedPattern
     */
    public function testGenerateGridElementIdWorks($gridName, $gridScope, $expectedPattern)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $grid */
        $grid = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');

        $grid->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($gridName));

        $grid->expects($this->atLeastOnce())
            ->method('getScope')
            ->will($this->returnValue($gridScope));

        $this->assertRegExp($expectedPattern, $this->twigExtension->generateGridElementId($grid));
    }

    /**
     * @return array
     */
    public function generateGridElementIdDataProvider()
    {
        return [
            [
                'test-grid',
                'test-scope',
                '/grid-test-grid-test-scope-[\d]+/',
            ],
            [
                'test-grid',
                '',
                '/grid-test-grid-[\d]+/',
            ],
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

    protected function tearDown()
    {
        unset(
            $this->datagridRouteHelper,
            $this->manager,
            $this->nameStrategy,
            $this->router,
            $this->securityFacade,
            $this->twigExtension
        );
    }
}

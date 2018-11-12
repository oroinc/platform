<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Twig;

use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\DataGridBundle\Twig\DataGridExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DataGridExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerInterface */
    protected $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|NameStrategyInterface */
    protected $nameStrategy;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RouterInterface */
    protected $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var DataGridExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridRouteHelper */
    protected $datagridRouteHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RequestStack */
    protected $requestStack;

    /** @var  \PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp()
    {
        $this->manager = $this->createMock(ManagerInterface::class);
        $this->nameStrategy = $this->createMock(NameStrategyInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->datagridRouteHelper = $this->createMock(DatagridRouteHelper::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_datagrid.datagrid.manager', $this->manager)
            ->add('oro_datagrid.datagrid.name_strategy', $this->nameStrategy)
            ->add('router', $this->router)
            ->add('security.authorization_checker', $this->authorizationChecker)
            ->add('oro_datagrid.helper.route', $this->datagridRouteHelper)
            ->add('request_stack', $this->requestStack)
            ->add('logger', $this->logger)
            ->getContainer($this);

        $this->extension = new DataGridExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_datagrid', $this->extension->getName());
    }

    public function testGetGridWorks()
    {
        $gridName = 'test-grid';
        $params = ['foo' => 'bar'];

        $grid = $this->createMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');

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

        $this->assertSame(
            $grid,
            self::callTwigFunction($this->extension, 'oro_datagrid_build', [$gridName, $params])
        );
    }

    public function testGetGridReturnsNullWhenConfigurationNotFound()
    {
        $gridName = 'test-grid';

        $this->manager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->will($this->returnValue(null));

        $this->assertNull(
            self::callTwigFunction($this->extension, 'oro_datagrid_build', [$gridName])
        );
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($acl)
            ->will($this->returnValue(false));

        $this->assertNull(
            self::callTwigFunction($this->extension, 'oro_datagrid_build', [$gridName])
        );
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

        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $grid */
        $grid = $this->createMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');
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

        $this->assertSame(
            $metadataArray,
            self::callTwigFunction($this->extension, 'oro_datagrid_metadata', [$grid, $params])
        );
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
        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $grid */
        $grid = $this->createMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');
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

        $this->assertSame(
            $gridDataArray,
            self::callTwigFunction($this->extension, 'oro_datagrid_data', [$grid])
        );
    }

    public function testGetGridDataException()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $grid */
        $grid = $this->createMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');
        $gridData = $this->getMockBuilder('Oro\\Bundle\\DataGridBundle\\Datagrid\\Common\\ResultsObject')
            ->disableOriginalConstructor()
            ->getMock();

        $grid->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($gridData));

        $exception = new \Exception('Page not found');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Getting grid data failed.',
                ['exception' => $exception]
            );

        $errorArray = [
            "data" => [],
            "metadata" => [],
            "options" => []
        ];

        $gridData->expects($this->once())
            ->method('toArray')
            ->willThrowException($exception);

        $this->assertSame(
            $errorArray,
            self::callTwigFunction($this->extension, 'oro_datagrid_data', [$grid])
        );
    }

    /**
     * @dataProvider generateGridElementIdDataProvider
     * @param string $gridName
     * @param string $gridScope
     * @param string $expectedPattern
     */
    public function testGenerateGridElementIdWorks($gridName, $gridScope, $expectedPattern)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $grid */
        $grid = $this->createMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');

        $grid->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($gridName));

        $grid->expects($this->atLeastOnce())
            ->method('getScope')
            ->will($this->returnValue($gridScope));

        $this->assertRegExp(
            $expectedPattern,
            self::callTwigFunction($this->extension, 'oro_datagrid_generate_element_id', [$grid])
        );
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

        $this->assertEquals(
            $expectedFullName,
            self::callTwigFunction($this->extension, 'oro_datagrid_build_fullname', [$gridName, $gridScope])
        );
    }

    public function testGetColumnAttributes()
    {
        $columnAttributes = [
            'name' => 'column1',
            'label' => 'Column 1',
            'type' => 'string'
        ];

        $metadata = $this->getMockBuilder(MetadataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->exactly(2))
            ->method('toArray')
            ->willReturn([
                'columns' => [$columnAttributes]
            ]);

        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $grid */
        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->exactly(2))
            ->method('getMetadata')
            ->willReturn($metadata);

        $this->assertEquals(
            $columnAttributes,
            self::callTwigFunction($this->extension, 'oro_datagrid_column_attributes', [$grid, 'column1'])
        );
        $this->assertEquals(
            [],
            self::callTwigFunction($this->extension, 'oro_datagrid_column_attributes', [$grid, 'column3'])
        );
    }

    /**
     * @dataProvider getPageUrlProvider
     *
     * @param string $queryString
     * @param integer $page
     * @param string $expectedParameters
     */
    public function testGetPageUrl($queryString, $page, $expectedParameters)
    {
        $gridName = 'test';

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getQueryString')->willReturn($queryString);
        $request->expects($this->once())->method('getPathInfo')->willReturn('test_url');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->any())->method('getName')->willReturn($gridName);

        $this->assertEquals(
            'test_url?' . $expectedParameters,
            self::callTwigFunction($this->extension, 'oro_datagrid_get_page_url', [$grid, $page])
        );
    }

    /**
     * @return array
     */
    public function getPageUrlProvider()
    {
        return [
            'with empty query string' => [
                'queryString' => '',
                'page' => 5,
                'expectedParameters' => 'grid%5Btest%5D=i%3D5'
            ],
            'with not empty query string but without grid params' => [
                'queryString' => 'foo=bar&bar=baz',
                'page' => 5,
                'expectedParameters' => 'foo=bar&bar=baz&grid%5Btest%5D=i%3D5'
            ],
            'with grid params in query sting' => [
                'queryString' => 'grid%5Btest%5D=i%3D4',
                'page' => 5,
                'expectedParameters' => 'grid%5Btest%5D=i%3D5'
            ],
        ];
    }
}

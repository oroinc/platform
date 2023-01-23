<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Twig;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
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

    /** @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var NameStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $nameStrategy;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var DatagridRouteHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridRouteHelper;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var DataGridExtension */
    private $extension;

    protected function setUp(): void
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
            ->add(RouterInterface::class, $this->router)
            ->add(AuthorizationCheckerInterface::class, $this->authorizationChecker)
            ->add('oro_datagrid.helper.route', $this->datagridRouteHelper)
            ->add(RequestStack::class, $this->requestStack)
            ->add(LoggerInterface::class, $this->logger)
            ->getContainer($this);

        $this->extension = new DataGridExtension($container);
    }

    public function testGetGridWorks()
    {
        $gridName = 'test-grid';
        $params = ['foo' => 'bar'];

        $grid = $this->createMock(DatagridInterface::class);

        $configuration = $this->createMock(DatagridConfiguration::class);
        $configuration->expects($this->once())
            ->method('getAclResource')
            ->willReturn(null);

        $this->manager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->willReturn($configuration);
        $this->manager->expects($this->once())
            ->method('getDatagridByRequestParams')
            ->with($gridName, $params)
            ->willReturn($grid);

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
            ->willReturn(null);

        $this->assertNull(
            self::callTwigFunction($this->extension, 'oro_datagrid_build', [$gridName])
        );
    }

    public function testGetGridReturnsNullWhenDontHavePermissions()
    {
        $gridName = 'test-grid';

        $acl = 'test-acl';

        $configuration = $this->createMock(DatagridConfiguration::class);

        $configuration->expects($this->once())
            ->method('getAclResource')
            ->willReturn($acl);

        $this->manager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->willReturn($configuration);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($acl)
            ->willReturn(false);

        $this->assertNull(
            self::callTwigFunction($this->extension, 'oro_datagrid_build', [$gridName])
        );
    }

    /**
     * @dataProvider routeProvider
     */
    public function testGetGridMetadataWorks(?string $configRoute, string $expectedRoute, string $requestMethod)
    {
        $gridName = 'test-grid';
        $gridScope = 'test-scope';
        $gridFullName = 'test-grid:test-scope';
        $params = ['foo' => 'bar'];
        $url = '/datagrid/test-grid?test-grid-test-scope=foo=bar';

        $grid = $this->createMock(DatagridInterface::class);
        $metadata = $this->createMock(MetadataObject::class);

        $grid->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metadata);

        $grid->expects($this->once())
            ->method('getName')
            ->willReturn($gridName);

        $grid->expects($this->once())
            ->method('getScope')
            ->willReturn($gridScope);

        $this->nameStrategy->expects($this->once())
            ->method('buildGridFullName')
            ->with($gridName, $gridScope)
            ->willReturn($gridFullName);

        $this->nameStrategy->expects($this->once())
            ->method('getGridUniqueName')
            ->with($gridFullName)
            ->willReturn($gridFullName);

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                $expectedRoute,
                ['gridName' => $gridFullName, $gridFullName => $params]
            )
            ->willReturn($url);

        $metadata->expects($this->once())
            ->method('offsetAddToArray')
            ->with('options', ['url' => $url, 'urlParams' => $params, 'type' => $requestMethod]);

        $metadata->expects($this->any())
            ->method('offsetGetByPath')
            ->with()
            ->willReturnMap([
                ['[options][route]', '', $configRoute],
                ['[options][requestMethod]', 'GET', $requestMethod],
            ]);

        $metadataArray = ['metadata-array'];
        $metadata->expects($this->once())
            ->method('toArray')
            ->willReturn($metadataArray);

        $this->assertSame(
            $metadataArray,
            self::callTwigFunction($this->extension, 'oro_datagrid_metadata', [$grid, $params])
        );
    }

    public function routeProvider(): array
    {
        return [
            [null, 'oro_datagrid_index', 'GET'],
            [null, 'oro_datagrid_index', 'POST'],
        ];
    }

    public function testGetGridDataWorks()
    {
        $grid = $this->createMock(DatagridInterface::class);
        $gridData = $this->createMock(ResultsObject::class);

        $grid->expects($this->once())
            ->method('getData')
            ->willReturn($gridData);

        $gridDataArray = ['grid-data'];

        $gridData->expects($this->once())
            ->method('toArray')
            ->willReturn($gridDataArray);

        $this->assertSame(
            $gridDataArray,
            self::callTwigFunction($this->extension, 'oro_datagrid_data', [$grid])
        );
    }

    public function testGetGridDataException()
    {
        $grid = $this->createMock(DatagridInterface::class);
        $gridData = $this->createMock(ResultsObject::class);

        $grid->expects($this->once())
            ->method('getData')
            ->willReturn($gridData);

        $exception = new \Exception('Page not found');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Getting grid data failed.',
                ['exception' => $exception]
            );

        $errorArray = [
            'data' => [],
            'metadata' => [],
            'options' => []
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
     */
    public function testGenerateGridElementIdWorks(string $gridName, string $gridScope, string $expectedPattern)
    {
        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->once())
            ->method('getName')
            ->willReturn($gridName);
        $grid->expects($this->atLeastOnce())
            ->method('getScope')
            ->willReturn($gridScope);

        $this->assertMatchesRegularExpression(
            $expectedPattern,
            self::callTwigFunction($this->extension, 'oro_datagrid_generate_element_id', [$grid])
        );
    }

    public function generateGridElementIdDataProvider(): array
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
            ->willReturn($expectedFullName);

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

        $metadata = $this->createMock(MetadataObject::class);
        $metadata->expects($this->exactly(2))
            ->method('toArray')
            ->willReturn([
                'columns' => [$columnAttributes]
            ]);

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
     */
    public function testGetPageUrl(string $queryString, int $page, string $expectedParameters)
    {
        $gridName = 'test';

        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn($queryString);
        $request->expects($this->once())
            ->method('getPathInfo')
            ->willReturn('test_url');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->any())
            ->method('getName')
            ->willReturn($gridName);

        $this->assertEquals(
            'test_url?' . $expectedParameters,
            self::callTwigFunction($this->extension, 'oro_datagrid_get_page_url', [$grid, $page])
        );
    }

    public function getPageUrlProvider(): array
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
            'with grid params in query string' => [
                'queryString' => 'grid%5Btest%5D=i%3D4',
                'page' => 5,
                'expectedParameters' => 'grid%5Btest%5D=i%3D5'
            ],
        ];
    }
}

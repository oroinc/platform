<?php

namespace Oro\Bundle\DataGridBundle\Twig;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for datagrid rendering:
 *   - oro_datagrid_build
 *   - oro_datagrid_data
 *   - oro_datagrid_metadata
 *   - oro_datagrid_generate_element_id
 *   - oro_datagrid_build_fullname
 *   - oro_datagrid_build_inputname
 *   - oro_datagrid_link
 *   - oro_datagrid_column_attributes
 *   - oro_datagrid_get_page_url
 */
class DataGridExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const ROUTE = 'oro_datagrid_index';

    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ManagerInterface
     */
    protected function getManager()
    {
        return $this->container->get('oro_datagrid.datagrid.manager');
    }

    /**
     * @return NameStrategyInterface
     */
    protected function getNameStrategy()
    {
        return $this->container->get('oro_datagrid.datagrid.name_strategy');
    }

    /**
     * @return RouterInterface
     */
    protected function getRouter()
    {
        return $this->container->get(RouterInterface::class);
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    protected function getAuthorizationChecker()
    {
        return $this->container->get(AuthorizationCheckerInterface::class);
    }

    /**
     * @return DatagridRouteHelper
     */
    protected function getRouteHelper()
    {
        return $this->container->get('oro_datagrid.helper.route');
    }

    /**
     * @return RequestStack
     */
    protected function getRequestStack()
    {
        return $this->container->get(RequestStack::class);
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->container->get(LoggerInterface::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_datagrid_build', [$this, 'getGrid']),
            new TwigFunction('oro_datagrid_data', [$this, 'getGridData']),
            new TwigFunction('oro_datagrid_metadata', [$this, 'getGridMetadata']),
            new TwigFunction('oro_datagrid_generate_element_id', [$this, 'generateGridElementId']),
            new TwigFunction('oro_datagrid_build_fullname', [$this, 'buildGridFullName']),
            new TwigFunction('oro_datagrid_build_inputname', [$this, 'buildGridInputName']),
            new TwigFunction('oro_datagrid_link', [$this, 'generateGridUrl']),
            new TwigFunction('oro_datagrid_column_attributes', [$this, 'getColumnAttributes']),
            new TwigFunction('oro_datagrid_get_page_url', [$this, 'getPageUrl']),
        ];
    }

    /**
     * @param string $name
     * @param array  $params
     *
     * @return DatagridInterface
     */
    public function getGrid($name, array $params = [])
    {
        if ($this->isAclGrantedForGridName($name)) {
            return $this->getManager()->getDatagridByRequestParams($name, $params);
        }

        return null;
    }

    /**
     * Returns grid metadata array
     *
     * @param DatagridInterface $grid
     * @param array             $params
     *
     * @return array
     */
    public function getGridMetadata(DatagridInterface $grid, array $params = [])
    {
        $metaData = $grid->getMetadata();
        $params = array_merge(
            $metaData->offsetGetByPath('[options][urlParams]') ?: [],
            $params
        );

        $route = (string) $metaData->offsetGetByPath('[options][route]', '');
        $type = $metaData->offsetGetByPath('[options][requestMethod]', Request::METHOD_GET);
        $metaData->offsetAddToArray(
            'options',
            [
                'url'       => $this->generateUrl($grid, $route, $params),
                'urlParams' => $params,
                'type'      => $type,
            ]
        );

        return $metaData->toArray();
    }

    /**
     * Renders grid data
     *
     * @param DatagridInterface $grid
     *
     * @return array
     */
    public function getGridData(DatagridInterface $grid)
    {
        try {
            return $grid->getData()->toArray();
        } catch (\Exception $e) {
            $this->getLogger()->error('Getting grid data failed.', ['exception' => $e]);

            return [
                "data"     => [],
                "metadata" => [],
                "options"  => []
            ];
        }
    }

    /**
     * Generate grid element id.
     *
     * @param DatagridInterface $grid
     *
     * @return string
     */
    public function generateGridElementId(DatagridInterface $grid)
    {
        $result = 'grid-' . $grid->getName() . '-';
        if ($grid->getScope()) {
            $result .= $grid->getScope() . '-';
        }
        $result .= mt_rand();

        return $result;
    }

    /**
     * Generate grid full name.
     *
     * @param string $name
     * @param string $scope
     *
     * @return string
     */
    public function buildGridFullName($name, $scope)
    {
        return $this->getNameStrategy()->buildGridFullName($name, $scope);
    }

    /**
     * Generate grid input name
     *
     * @param string $name
     *
     * @return string
     */
    public function buildGridInputName($name)
    {
        return $this->getNameStrategy()->getGridUniqueName($name);
    }

    /**
     * @param DatagridInterface $grid
     * @param string            $columnName
     *
     * @return array|null
     */
    public function getColumnAttributes(DatagridInterface $grid, $columnName)
    {
        $metadata = $grid->getMetadata()->toArray();

        if (array_key_exists('columns', $metadata)) {
            foreach ($metadata['columns'] as $column) {
                if ($columnName === $column['name']) {
                    return $column;
                }
            }
        }

        return [];
    }

    /**
     * Generates url based on current uri with replaced current page parameter
     *
     * @param DatagridInterface $grid
     * @param integer           $page
     *
     * @return string
     */
    public function getPageUrl(DatagridInterface $grid, $page)
    {
        $request = $this->getRequestStack()->getCurrentRequest();

        $queryString = $request->getQueryString();
        parse_str($queryString, $queryStringComponents);

        $gridParams = [];
        if (isset($queryStringComponents['grid'][$grid->getName()])) {
            parse_str($queryStringComponents['grid'][$grid->getName()], $gridParams);
        }

        $gridParams['i'] = $page;
        $queryStringComponents['grid'][$grid->getName()] = http_build_query($gridParams);

        return $request->getPathInfo() . '?' . http_build_query($queryStringComponents);
    }

    /**
     * @param string $routeName
     * @param string $gridName
     * @param array  $params
     * @param int    $referenceType
     *
     * @return string
     */
    public function generateGridUrl(
        string $routeName,
        string $gridName,
        array $params = [],
        int $referenceType = RouterInterface::ABSOLUTE_PATH
    ): string {
        return $this->getRouteHelper()->generate($routeName, $gridName, $params, $referenceType);
    }

    /**
     * @param DatagridInterface $grid
     * @param string            $route
     * @param array             $params
     *
     * @return string
     */
    protected function generateUrl(DatagridInterface $grid, string $route, array $params): string
    {
        $nameStrategy = $this->getNameStrategy();
        $gridFullName = $nameStrategy->buildGridFullName($grid->getName(), $grid->getScope());
        $gridUniqueName = $nameStrategy->getGridUniqueName($gridFullName);

        return $this->getRouter()->generate(
            $route ?: self::ROUTE,
            ['gridName' => $gridFullName, $gridUniqueName => $params]
        );
    }

    /**
     * @param string $gridName
     *
     * @return bool
     */
    protected function isAclGrantedForGridName($gridName)
    {
        $gridConfig = $this->getManager()->getConfigurationForGrid($gridName);

        if ($gridConfig) {
            $aclResource = $gridConfig->getAclResource();
            if ($aclResource && !$this->getAuthorizationChecker()->isGranted($aclResource)) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_datagrid.datagrid.manager' => ManagerInterface::class,
            'oro_datagrid.datagrid.name_strategy' => NameStrategyInterface::class,
            'oro_datagrid.helper.route' => DatagridRouteHelper::class,
            RouterInterface::class,
            AuthorizationCheckerInterface::class,
            RequestStack::class,
            LoggerInterface::class,
        ];
    }
}

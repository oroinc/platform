<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Symfony\Component\Routing\RouterInterface;

use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class DatagridRouteHelper
{
    /** @var RouterInterface */
    protected $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Generates URL or URI to the page where Datagrid placed with properly configured query sting
     * to apply filter criteria
     *
     * @param string $routeName
     * @param string $gridName
     * @param array $params
     * @param int $referenceType
     *
     * @return string
     *
     * @throws RouteNotFoundException              If the named route doesn't exist
     * @throws MissingMandatoryParametersException When some parameters are missing that are mandatory for the route
     * @throws InvalidParameterException           When a parameter value for a placeholder is not correct because
     *                                             it does not match the requirement
     */
    public function generate($routeName, $gridName, array $params = [], $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        return $this->router->generate(
            $routeName,
            ['grid' => [$gridName => http_build_query($params)]],
            $referenceType
        );
    }
}

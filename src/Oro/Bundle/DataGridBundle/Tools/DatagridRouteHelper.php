<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class DatagridRouteHelper
{
    /** @var Router */
    protected $router;

    /**
     * DatagridRouteHelper constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $name
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
    public function generate($name, $gridName, $params = [], $referenceType = Router::ABSOLUTE_PATH)
    {
        return $this->router->generate(
            $name,
            ['grid' => [$gridName => http_build_query($params)]],
            $referenceType
        );
    }
}

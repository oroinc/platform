<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use GuzzleHttp\Psr7\Uri as GuzzleUri;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Contains handy methods for working with datagrid URL.
 */
class DatagridRouteHelper
{
    /** @var RouterInterface */
    protected $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Generates URL or URI to the page where Datagrid placed with properly configured query string
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
    public function generate(
        string $routeName,
        string $gridName,
        array $params = [],
        int $referenceType = RouterInterface::ABSOLUTE_PATH
    ) {
        return $this->router->generate(
            $routeName,
            ['grid' => [$gridName => http_build_query($params)]],
            $referenceType
        );
    }

    /**
     * Adds minified grid parameters to the specified URL.
     */
    public function appendGridParams(string $originalUrl, string $gridName, array $params): string
    {
        if (!$gridName || !$params) {
            return $originalUrl;
        }

        $uri = new GuzzleUri($originalUrl);

        return GuzzleUri::withQueryValue(
            $uri,
            sprintf('%s[%s]', RequestParameterBagFactory::DEFAULT_ROOT_PARAM, $gridName),
            http_build_query($params)
        );
    }
}

<?php

namespace Oro\Bundle\DataGridBundle\Converter;

use Oro\Bundle\ActionBundle\Helper\DefaultOperationRequestHelper;
use Symfony\Component\Routing\RouterInterface;

/**
 * Converting ajax grid URL to the page URL where grid originally located.
 * Based on URL parameter "originalRoute".
 * Warning: can give incorrect result in a case when parameter "originalRoute" missed in URL!
 */
class UrlConverter
{
    /** @var RouterInterface */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $datagridName
     * @param string $url
     *
     * @return string
     */
    public function convertGridUrlToPageUrl(string $datagridName, string $url)
    {
        $urlParameters = [];

        $parts = explode('?', $url);
        $baseUrl = $parts[0];
        $urlParametersStr = $parts[1] ?? '';

        parse_str($urlParametersStr, $urlParameters);
        if (empty($urlParameters[$datagridName][DefaultOperationRequestHelper::ORIGINAL_ROUTE_URL_PARAMETER_KEY])) {
            return $url;
        }

        $originalRoute = $urlParameters[$datagridName][DefaultOperationRequestHelper::ORIGINAL_ROUTE_URL_PARAMETER_KEY];
        $originalRouteUrl = $this->router->generate($originalRoute);
        if ($originalRouteUrl === $baseUrl) {
            return $url;
        }

        $urlParams = [
            $datagridName => $urlParameters[$datagridName]
        ];
        return $originalRouteUrl . '?' . \http_build_query($urlParams);
    }
}

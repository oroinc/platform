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
    public function __construct(private RouterInterface $router)
    {
    }

    public function convertGridUrlToPageUrl(string $datagridName, string $url): string
    {
        $urlParameters = [];

        $parts = explode('?', $url);
        $baseUrl = $parts[0];
        $urlParametersStr = $parts[1] ?? '';

        parse_str($urlParametersStr, $urlParameters);
        if (empty($urlParameters[$datagridName][DefaultOperationRequestHelper::ORIGINAL_ROUTE_URL_PARAMETER_KEY])) {
            return $url;
        }

        $datagridParams = $urlParameters[$datagridName];
        $originalRoute = $datagridParams[DefaultOperationRequestHelper::ORIGINAL_ROUTE_URL_PARAMETER_KEY];
        $originalRouteParameters = $datagridParams[DefaultOperationRequestHelper::ORIGINAL_ROUTE_PARAMETERS_KEY] ?? '';
        $originalRouteParameters = json_decode(urldecode($originalRouteParameters), true) ?: [];

        $originalRouteUrl = $this->router->generate($originalRoute, $originalRouteParameters);
        if ($originalRouteUrl === $baseUrl) {
            return $url;
        }

        $urlParams = [
            $datagridName => $urlParameters[$datagridName]
        ];
        return $originalRouteUrl . '?' . \http_build_query($urlParams);
    }
}

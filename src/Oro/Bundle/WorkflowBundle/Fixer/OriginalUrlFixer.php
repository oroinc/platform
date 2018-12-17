<?php

namespace Oro\Bundle\WorkflowBundle\Fixer;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Helper\DefaultOperationRequestHelper;
use Symfony\Component\Routing\RouterInterface;

/**
 * @deprecated Will be removed at 3.0
 */
class OriginalUrlFixer
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
     * @param ButtonContext $buttonContext
     */
    public function fixGridAjaxUrl(ButtonContext $buttonContext)
    {
        $originalUrl = $buttonContext->getOriginalUrl();
        $datagridName = $buttonContext->getDatagridName();
        if (!$originalUrl || !$datagridName) {
            return;
        }

        $urlParameters = [];

        $parts = explode('?', $originalUrl);
        $baseUrl = $parts[0];
        $urlParametersStr = $parts[1] ?? '';

        /**
         * Check that url contains original route parameters
         * that exists for all grids that contain action buttons
         */
        parse_str($urlParametersStr, $urlParameters);
        if (empty($urlParameters[$datagridName][DefaultOperationRequestHelper::ORIGINAL_ROUTE_PARAMETER_KEY])) {
            return;
        }

        $originalRoute = $urlParameters[$datagridName][DefaultOperationRequestHelper::ORIGINAL_ROUTE_PARAMETER_KEY];
        $originalUrl = $this->router->generate($originalRoute);
        /**
         * If base url is not the same as original url then we must construct
         * new original URL to prevent issue with incorrect redirect (f.e. from transition page)
         */
        if ($originalUrl === $baseUrl) {
            return;
        }

        $urlParams = [
            $datagridName => $urlParameters[$datagridName]
        ];
        $newOriginalUrl = $originalUrl . '?' . \http_build_query($urlParams);
        $buttonContext->setOriginalUrl($newOriginalUrl);
    }
}

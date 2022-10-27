<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Helper responsible for generating urls for needed for action.
 */
class ApplicationsUrlHelper
{
    private RouteProviderInterface $routeProvider;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(RouteProviderInterface $routeProvider, UrlGeneratorInterface $urlGenerator)
    {
        $this->routeProvider = $routeProvider;
        $this->urlGenerator = $urlGenerator;
    }

    public function getExecutionUrl(array $parameters = []): string
    {
        return $this->generateUrl($this->routeProvider->getExecutionRoute(), $parameters);
    }

    public function getDialogUrl(array $parameters = []): string
    {
        return $this->generateUrl($this->routeProvider->getFormDialogRoute(), $parameters);
    }

    public function getPageUrl(array $parameters = []): string
    {
        return $this->generateUrl($this->routeProvider->getFormPageRoute(), $parameters);
    }

    private function generateUrl(string $routeName, array $parameters = []): string
    {
        return $this->urlGenerator->generate($routeName, $parameters);
    }
}

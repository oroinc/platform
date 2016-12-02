<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Symfony\Component\Routing\RouterInterface;

class ApplicationsUrlHelper
{
    /** @var RouteProviderInterface */
    private $routeProvider;

    /** @var RouterInterface */
    private $router;

    /**
     * @param RouteProviderInterface $routeProvider
     * @param RouterInterface $router
     */
    public function __construct(RouteProviderInterface $routeProvider, RouterInterface $router)
    {
        $this->routeProvider = $routeProvider;
        $this->router = $router;
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function getExecutionUrl(array $parameters = [])
    {
        return $this->generateUrl($this->routeProvider->getExecutionRoute(), $parameters);
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function getDialogUrl(array $parameters = [])
    {
        return $this->generateUrl($this->routeProvider->getFormDialogRoute(), $parameters);
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function getPageUrl(array $parameters = [])
    {
        return $this->generateUrl($this->routeProvider->getFormPageRoute(), $parameters);
    }

    /**
     * @param string $routeName
     * @param array $parameters
     *
     * @return string
     */
    private function generateUrl($routeName, array $parameters = [])
    {
        return $this->router->generate($routeName, $parameters);
    }
}

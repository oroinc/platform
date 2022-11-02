<?php

namespace Oro\Bundle\UIBundle\Provider;

use Symfony\Component\Routing\RouterInterface;

/**
 * Generates url without front controller.
 */
class UrlWithoutFrontControllerProvider
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function generate(string $name, array $parameters = [])
    {
        $prevBaseUrl = $this->router->getContext()->getBaseUrl();
        $baseUrlWithoutFrontController = preg_replace('/\/[\w\_]+\.php$/', '', $prevBaseUrl);
        $this->router->getContext()->setBaseUrl($baseUrlWithoutFrontController);
        $url = $this->router->generate($name, $parameters);
        $this->router->getContext()->setBaseUrl($prevBaseUrl);

        return $url;
    }
}

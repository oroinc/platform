<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Makes reusable logic of preparing path
 * Should be replaced by decoration in future
 */
trait UrlProviderTrait
{
    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

    /**
     * Builds valid url based on Application URL (from config) and generated absolute path (by route name and params)
     * considering url parts should not be duplicated in result value
     * @param string $url
     * @param string $route
     * @param array $routeParams
     * @return string
     */
    protected function preparePath($url, $route, array $routeParams): string
    {
        $url = rtrim($url, '/'); // Clears slash at the end of application url

        $urlParts = parse_url($url);

        $absoluteUrlPath = $this->urlGenerator->generate($route, $routeParams);

        //Checks if application url path is already present in generated absolute path
        //So we need only protocol part and domain (considering it is always present in $urlParts array)
        if (isset($urlParts['path']) && str_starts_with($absoluteUrlPath, $urlParts['path'])) {
            $url = $urlParts['scheme'] . '://' . $urlParts['host'];
            if (isset($urlParts['port'])) {
                $url .= ':' . $urlParts['port'];
            }
        }

        return $url . $absoluteUrlPath;
    }
}

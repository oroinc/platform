<?php

namespace Oro\Bundle\ApiBundle\Asset;

use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Decorates Packages to convert relative asset URLs to absolute for API requests.
 * Only processes /build/ paths to avoid affecting other assets.
 *
 *  Extends Packages only to satisfy strict type-hints.
 *  All behavior is delegated to the decorated Packages instance.
 */
class ApiAbsoluteUrlAssetPackages extends Packages
{
    public function __construct(
        private readonly Packages $innerPackages,
        private readonly RequestStack $requestStack,
        private readonly ApiUrlResolver $apiUrlResolver
    ) {
    }

    public function getUrl(string $path, ?string $packageName = null): string
    {
        $url = $this->innerPackages->getUrl($path, $packageName);

        if (!$this->apiUrlResolver->shouldUseAbsoluteUrls()) {
            return $url;
        }

        if (preg_match('~^https?://~', $url)) {
            return $url;
        }

        if (str_starts_with($url, '/build/')) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                return $request->getSchemeAndHttpHost() . $url;
            }
        }

        return $url;
    }

    public function getVersion(string $path, ?string $packageName = null): string
    {
        return $this->innerPackages->getVersion($path, $packageName);
    }

    public function getPackage(?string $name = null): PackageInterface
    {
        return $this->innerPackages->getPackage($name);
    }

    public function setDefaultPackage(PackageInterface $defaultPackage): void
    {
        $this->innerPackages->setDefaultPackage($defaultPackage);
    }

    public function addPackage(string $name, PackageInterface $package): void
    {
        $this->innerPackages->addPackage($name, $package);
    }
}

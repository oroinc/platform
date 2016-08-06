<?php

namespace Oro\Bundle\LayoutBundle\Layout\DataProvider;

use Symfony\Component\Asset\Packages;

use Oro\Component\Layout\AbstractServerRenderDataProvider;

class AssetProvider extends AbstractServerRenderDataProvider
{
    /** @var Packages */
    protected $packages;

    /**
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    public function getUrl($path, $packageName = null)
    {
        if ($path === null) {
            return $path;
        }
        if (!is_string($path)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected a string value for the path, got "%s".',
                    is_object($path) ? get_class($path) : gettype($path)
                )
            );
        }

        if ($packageName !== null && !is_string($packageName)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected null or a string value for the package name, got "%s".',
                    is_object($packageName) ? get_class($packageName) : gettype($packageName)
                )
            );
        }

        return $this->packages->getUrl($this->normalizeAssetsPath($path), $packageName);
    }

    /**
     * Normalizes assets path
     * E.g. '@AcmeTestBundle/Resources/public/images/picture.png' => 'bundles/acmetest/images/picture.png'
     *
     * @param string $path
     *
     * @return string
     */
    private function normalizeAssetsPath($path)
    {
        if ($path && '@' === $path[0]) {
            $trimmedPath = substr($path, 1);
            $normalizedPath = preg_replace_callback(
                '#(\w+)Bundle/Resources/public#',
                function ($matches) {
                    return strtolower($matches[1]);
                },
                $trimmedPath
            );
            if ($normalizedPath && $normalizedPath !== $trimmedPath) {
                $path = 'bundles/'.$normalizedPath;
            }
        }

        return $path;
    }
}

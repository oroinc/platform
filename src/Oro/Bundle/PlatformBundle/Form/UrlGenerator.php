<?php

namespace Oro\Bundle\PlatformBundle\Form;

use Oro\Bundle\PlatformBundle\Provider\PackageProvider;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Url generator for request form
 */
class UrlGenerator
{
    const URL = '//r.orocrm.com/a/';
    const FORM_JS = 'f.js';

    private PackageProvider $packageProvider;
    private CacheInterface $cacheProvider;
    private static array $aliases = [
        PackageProvider::PACKAGE_PLATFORM => 'p',
        PackageProvider::PACKAGE_COMMERCE => 'o',
        PackageProvider::PACKAGE_CRM => 'r',
    ];

    public function __construct(PackageProvider $packageProvider, CacheInterface $cacheProvider)
    {
        $this->packageProvider = $packageProvider;
        $this->cacheProvider = $cacheProvider;
    }

    public function getFormUrl(): string
    {
        return $this->cacheProvider->get('url', function () {
            $packages = $this->packageProvider->getOroPackages(false);
            $url = self::URL;
            foreach ($packages as $packageName => $packageData) {
                $packageName = str_replace(
                    PackageProvider::ORO_NAMESPACE . PackageProvider::NAMESPACE_DELIMITER,
                    '',
                    $packageName
                );
                if (!array_key_exists($packageName, self::$aliases)) {
                    continue;
                }
                $url .= sprintf('%s/%s/', self::$aliases[$packageName], $packageData['pretty_version']);
            }
            $url .= self::FORM_JS;
            return $url;
        });
    }
}

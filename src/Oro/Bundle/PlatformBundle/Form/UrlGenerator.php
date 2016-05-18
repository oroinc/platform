<?php

namespace Oro\Bundle\PlatformBundle\Form;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\PlatformBundle\Provider\PackageProvider;

class UrlGenerator
{
    const URL = '//r.orocrm.com/a/';
    const FORM_JS = 'f.js';

    /** @var PackageProvider */
    protected $packageProvider;

    /** @var CacheProvider */
    protected $cacheProvider;

    /**
     * @var array
     */
    private static $aliases = [
        PackageProvider::PACKAGE_PLATFORM => 'p',
        PackageProvider::PACKAGE_COMMERCE => 'o',
        PackageProvider::PACKAGE_CRM => 'r',
    ];

    /**
     * @param PackageProvider $packageProvider
     * @param CacheProvider $cacheProvider
     */
    public function __construct(PackageProvider $packageProvider, CacheProvider $cacheProvider)
    {
        $this->packageProvider = $packageProvider;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @return string
     */
    public function getFormUrl()
    {
        if ($this->cacheProvider->contains('url')) {
            return $this->cacheProvider->fetch('url');
        }

        $packages = $this->packageProvider->getOroPackages(false);
        $url = self::URL;

        foreach ($packages as $package) {
            $packageName = str_replace(
                PackageProvider::ORO_NAMESPACE . PackageProvider::NAMESPACE_DELIMITER,
                '',
                $package->getPrettyName()
            );

            if (!array_key_exists($packageName, self::$aliases)) {
                continue;
            }

            $url .= sprintf('%s/%s/', self::$aliases[$packageName], $package->getPrettyVersion());
        }

        $url .= self::FORM_JS;

        $this->cacheProvider->save('url', $url);

        return $url;
    }
}

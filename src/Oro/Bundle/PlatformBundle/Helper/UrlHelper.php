<?php

namespace Oro\Bundle\PlatformBundle\Helper;

use Doctrine\Common\Cache\CacheProvider;

class UrlHelper
{
    const URL = '//crm.orocrm.com/orocrmrequest';

    /** @var PackageHelper */
    protected $packageHelper;

    /** @var CacheProvider */
    protected $cacheProvider;

    /**
     * @param PackageHelper $packageHelper
     * @param CacheProvider $cacheProvider
     */
    public function __construct(PackageHelper $packageHelper, CacheProvider $cacheProvider)
    {
        $this->packageHelper = $packageHelper;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if ($this->cacheProvider->contains('url')) {
            return $this->cacheProvider->fetch('url');
        }

        $packages = $this->packageHelper->getOroPackages(false);
        $url = self::URL;

        foreach ($packages as $package) {
            $url .= sprintf(
                '/%s/%s',
                str_replace(
                    PackageHelper::ORO_NAMESPACE . PackageHelper::NAMESPACE_DELIMITER,
                    '',
                    $package->getPrettyName()
                ),
                $package->getPrettyVersion()
            );
        }

        $this->cacheProvider->save('url', $url);

        return $url;
    }
}

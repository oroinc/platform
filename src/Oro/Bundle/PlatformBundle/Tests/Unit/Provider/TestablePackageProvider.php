<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider;

use Oro\Bundle\PlatformBundle\Provider\PackageProvider;

/**
 * Extends 'PackageProvider' class with mocked composer api packages handling flow.
 */
class TestablePackageProvider extends PackageProvider
{
    private array $installedPackages = [];

    public function setInstalledPackages($installedPackages): void
    {
        $this->installedPackages = $installedPackages;
    }

    protected function getInstalledPackages(): array
    {
        return $this->installedPackages;
    }
}

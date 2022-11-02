<?php

namespace Oro\Bundle\PlatformBundle\Provider;

use Composer\InstalledVersions;

/**
 * Provides information about packages.
 */
class PackageProvider
{
    const ORO_NAMESPACE = 'oro';
    const NAMESPACE_DELIMITER = '/';

    const PACKAGE_PLATFORM = 'platform';
    const PACKAGE_CRM = 'crm';
    const PACKAGE_COMMERCE = 'commerce';

    protected array $oroPackages = [];

    protected array $thirdPartyPackages = [];

    private ?string $installedJsonfile;

    public function __construct(?string $installedJsonfile = null)
    {
        $this->installedJsonfile = $installedJsonfile;
    }

    /**
     * @param bool $additional
     * @return array ['package_name' => ['pretty_version' => '1.0.0', 'license' => ['MIT']]]
     */
    public function getOroPackages(bool $additional = true): array
    {
        $this->initPackages();

        if (false === $additional) {
            return array_intersect_key(
                $this->oroPackages,
                array_flip(
                    [
                        self::ORO_NAMESPACE . self::NAMESPACE_DELIMITER . self::PACKAGE_PLATFORM,
                        self::ORO_NAMESPACE . self::NAMESPACE_DELIMITER . self::PACKAGE_CRM,
                        self::ORO_NAMESPACE . self::NAMESPACE_DELIMITER . self::PACKAGE_COMMERCE,
                    ]
                )
            );
        }

        return $this->oroPackages;
    }

    /**
     * @return array ['package_name' => ['pretty_version' => '1.0.0', 'license' => ['MIT']]]
     */
    public function getThirdPartyPackages(): array
    {
        $this->initPackages();

        return $this->thirdPartyPackages;
    }

    protected function getInstalledPackages(): array
    {
        $packages = [];
        foreach (InstalledVersions::getAllRawData() as $installed) {
            $rootPackageName = isset($installed['root']) ? $installed['root']['name'] : null;
            foreach ($installed['versions'] as $packageName => $packageData) {
                if (isset($packageData['reference'])
                    && $packageName !== $rootPackageName
                    && !isset($packages[$packageName])
                ) {
                    $packages[$packageName]['pretty_version'] = $packageData['pretty_version'];
                }
            }
        }

        return $packages;
    }

    private function initPackages(): void
    {
        if (!empty($this->oroPackages) && !empty($this->thirdPartyPackages)) {
            return;
        }

        $packages = $this->getInstalledPackages();
        $licenses = $this->retrieveLicenses();

        foreach ($packages as $packageName => $packageData) {
            $packageData['license'] = $licenses[$packageName] ?? [];
            if ($this->isOroPackage($packageName)) {
                $this->oroPackages[$packageName] = $packageData;
            } else {
                $this->thirdPartyPackages[$packageName] = $packageData;
            }
        }
    }

    private function retrieveLicenses(): array
    {
        if (null === $this->installedJsonfile) {
            return [];
        }

        if (!\file_exists($this->installedJsonfile)) {
            throw new \RuntimeException(sprintf('File "%s" does not exists.', $this->installedJsonfile));
        }

        $data = json_decode(file_get_contents($this->installedJsonfile), true);
        $licenses = [];
        foreach ($data['packages'] as $packageData) {
            $licenses[$packageData['name']] = $packageData['license'] ?? [];
        }

        return $licenses;
    }

    private function isOroPackage(string $packageName): bool
    {
        return str_starts_with($packageName, self::ORO_NAMESPACE . self::NAMESPACE_DELIMITER);
    }
}

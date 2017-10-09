<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Composer\Composer;
use Oro\Bundle\PlatformBundle\Composer\LocalRepositoryFactory;
use Symfony\Component\Config\FileLocator;

class TranslationPackagesProviderExtension implements TranslationPackagesProviderExtensionInterface
{
    /** @var LocalRepositoryFactory */
    protected $localRepositoryFactory;

    /** @var Composer */
    protected $composer;

    /** @var array */
    protected $packages = [];

    /**
     * @param LocalRepositoryFactory $localRepositoryFactory
     * @param Composer $composer
     */
    public function __construct(LocalRepositoryFactory $localRepositoryFactory, Composer $composer)
    {
        $this->localRepositoryFactory = $localRepositoryFactory;
        $this->composer = $composer;
    }

    /**
     * @param $packageAlias
     * @param string $packageName
     * @param string $suffix
     *
     * @return $this
     */
    public function addPackage($packageAlias, $packageName, $suffix = '')
    {
        $this->packages[] = [$packageAlias, $packageName, $suffix];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageNames()
    {
        return array_unique(
            array_map(
                function (array $package) {
                    return $package[0];
                },
                $this->packages
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPackagePaths()
    {
        $localRepository = $this->localRepositoryFactory->getLocalRepository();
        $manager = $this->composer->getInstallationManager();

        $paths = [];
        foreach ($this->packages as $package) {
            $composerPackage = $localRepository->findPackage($package[1], '*');
            if (null === $composerPackage) {
                continue;
            }

            $paths[] = $manager->getInstallPath($composerPackage) . $package[2];
        }

        return new FileLocator($paths);
    }
}

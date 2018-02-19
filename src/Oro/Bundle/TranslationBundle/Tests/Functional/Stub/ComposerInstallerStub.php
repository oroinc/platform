<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Stub;

use Composer\Installer\InstallerInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

class ComposerInstallerStub implements InstallerInterface
{
    /** @var string */
    protected $installPath;

    /**
     * @param string $installPath
     */
    public function __construct(string $installPath)
    {
        $this->installPath = $installPath;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($packageType)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        return $this->installPath;
    }
}

<?php
namespace Oro\Bundle\DistributionBundle\Manager;

use Composer\Composer;
use Composer\Package\PackageInterface;

class PackageManager
{
    protected $composer;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    /**
     * @return PackageInterface[]
     */
    public function getInstalled()
    {
        return $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
    }
}
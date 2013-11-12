<?php
namespace Oro\Bundle\DistributionBundle\Manager;

use Oro\Bundle\DistributionBundle\Entity\Package;
use Oro\Bundle\DistributionBundle\Repository\PackageRepository;
use Oro\Bundle\DistributionBundle\Storage\PackageStorage;

class PackageManager
{
    /**
     * @var PackageRepository
     */
    protected $repository;

    /**
     * @var PackageStorage
     */
    protected $storage;

    /**
     * @param PackageRepository $repository
     * @param PackageStorage $storage
     */
    public function __construct(PackageRepository $repository, PackageStorage $storage)
    {
        $this->repository = $repository;
        $this->storage = $storage;
    }

    /**
     * @return Package[]
     */
    public function getInstalled()
    {
        return [
            new Package(),
            new Package(),
            new Package(),
            new Package()
        ];
    }
}
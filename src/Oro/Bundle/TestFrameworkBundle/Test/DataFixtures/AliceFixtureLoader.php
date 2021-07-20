<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Fidry\AliceDataFixtures\LoaderInterface;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Symfony\Component\Config\FileLocator;

/**
 * Aware about loaded objects.
 */
class AliceFixtureLoader implements LoaderInterface
{
    /** @var LoaderInterface */
    protected $loader;

    /** @var Collection */
    protected $referenceRepository;

    /** @var FileLocator */
    protected $fileLocator;

    public function __construct(LoaderInterface $loader, FileLocator $fileLocator)
    {
        $this->loader = $loader;
        $this->fileLocator = $fileLocator;
        $this->referenceRepository = new Collection();
    }

    public function getReferenceRepository(): Collection
    {
        return $this->referenceRepository;
    }

    protected function setReferences(array $references): void
    {
        $this->referenceRepository->clear();
        foreach ($references as $key => $object) {
            $this->referenceRepository->set($key, $object);
        }
    }

    /**
     * @param array $dataOrFiles
     * @param array $parameters
     * @param array $objects
     * @param PurgeMode|null $purgeMode
     * @return object[]
     */
    public function load(
        array $dataOrFiles,
        array $parameters = [],
        array $objects = [],
        PurgeMode $purgeMode = null
    ): array {
        $referenceRepositoryObjects = array_filter($this->referenceRepository->toArray());
        $objects = $this->loader->load(
            $dataOrFiles,
            $parameters,
            \array_merge($referenceRepositoryObjects, $objects)
        );

        $added = \array_filter(
            $objects,
            function ($object) {
                return !$this->referenceRepository->contains($object);
            }
        );

        $this->setReferences($objects);

        return $added;
    }

    /**
     * @param string $file
     * @return string Full path to file
     */
    public function locateFile(string $file)
    {
        return $this->fileLocator->locate($file);
    }
}

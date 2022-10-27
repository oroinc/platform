<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Loader;

use Fidry\AliceDataFixtures\LoaderInterface;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Nelmio\Alice\DataLoaderInterface;
use Nelmio\Alice\FilesLoaderInterface;

/**
 * Loads the fixtures files or data array and return the loaded objects.
 */
class AliceCombinedLoader implements LoaderInterface
{
    /** @var FilesLoaderInterface */
    protected $filesLoader;

    /** @var DataLoaderInterface */
    protected $dataLoader;

    /** @var array */
    protected $loadedParameters = [];

    public function __construct(FilesLoaderInterface $filesLoader, DataLoaderInterface $dataLoader)
    {
        $this->filesLoader = $filesLoader;
        $this->dataLoader = $dataLoader;
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
        if (!$dataOrFiles) {
            return [];
        }

        $parameters = array_merge($this->loadedParameters, $parameters);

        $firstItem = reset($dataOrFiles);

        $result = \is_string($firstItem) && \is_file($firstItem)
            ? $this->filesLoader->loadFiles($dataOrFiles, $parameters, $objects)
            : $this->dataLoader->loadData($dataOrFiles, $parameters, $objects);

        $this->loadedParameters = $result->getParameters();

        return $result->getObjects();
    }
}

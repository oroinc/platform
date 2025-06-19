<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * Loads data mapping for complex data import and export.
 */
class ComplexDataStaticMappingProvider extends PhpArrayConfigProvider implements ComplexDataMappingProviderInterface
{
    public function __construct(
        private readonly string $rootName,
        private readonly string $configFile,
        string $cacheFile,
        bool $debug
    ) {
        parent::__construct($cacheFile, $debug);
    }

    #[\Override]
    public function getMapping(array $mapping): array
    {
        return array_merge($mapping, $this->doGetConfig());
    }

    #[\Override]
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer): array
    {
        $configs = [];
        $configLoader = CumulativeConfigLoaderFactory::create($this->rootName, $this->configFile);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (!empty($resource->data)) {
                $configs[] = $resource->data;
            }
        }

        return CumulativeConfigProcessorUtil::processConfiguration(
            $this->configFile,
            new ComplexDataMappingConfiguration($this->rootName),
            $configs
        );
    }
}

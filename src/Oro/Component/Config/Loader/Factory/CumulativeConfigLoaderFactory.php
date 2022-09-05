<?php

namespace Oro\Component\Config\Loader\Factory;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\FolderYamlCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

/**
 * A factory that return instance of CumulativeConfigLoader with included Yaml file loaders.
 *
 * Example:
 * CumulativeConfigLoaderFactory::create('oro_features', 'Resources/config/oro/features.yml')
 * It should be load configuration:
 * [
 *      "BarBundle/Resources/config/oro/features.yml",
 *      "FooBundle/Resources/config/oro/features.yml",
 *      ...
 *      "config/oro/features/bar_features.yml",
 *      "config/oro/features/foo_features.yml",
 *      ...
 * ]
 */
class CumulativeConfigLoaderFactory
{
    public static function create(string $configurationName, string $yamlFilePath): CumulativeConfigLoader
    {
        $yamlFileLoaders = [
            new YamlCumulativeFileLoader($yamlFilePath)
        ];
        // Load configuration from application also
        $appYmlDirPath = '../' . str_replace(['Resources/', '.yml'], '', $yamlFilePath);
        $yamlFileLoaders[] = new FolderYamlCumulativeFileLoader($appYmlDirPath);

        return new CumulativeConfigLoader(
            $configurationName,
            $yamlFileLoaders
        );
    }
}

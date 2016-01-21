<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class PermissionConfigurationProvider
{
    const ROOT_NODE_NAME = 'permissions';

    /** @var PermissionDefinitionListConfiguration */
    protected $definitionConfiguration;

    /** @var array */
    protected $kernelBundles;

    /** @var array */
    protected $processedConfigs = [];

    /**
     * @var string
     */
    protected $configFilePattern = 'permission.yml';

    /**
     * @var string
     */
    protected $configDirectory = '/Resources/config/';

    /**
     * @param PermissionDefinitionListConfiguration $definitionConfiguration
     * @param array $kernelBundles
     */
    public function __construct(
        array $kernelBundles,
        PermissionDefinitionListConfiguration $definitionConfiguration
    ) {
        $this->kernelBundles = array_values($kernelBundles);
        $this->definitionConfiguration = $definitionConfiguration;
    }

    /**
     * @param array|null $usedDirectories
     * @param array|null $usedDefinitions
     * @return array
     * @throws InvalidConfigurationException
     */
    public function getPermissionConfiguration(
        array $usedDirectories = null,
        array $usedDefinitions = null
    ) {
        $finder = $this->getConfigFinder((array)$usedDirectories);

        $definitions = array();
        $triggers = array();

        /** @var $file \SplFileInfo */
        foreach ($finder as $file) {
            $realPathName = $file->getRealPath();
            $configData = $this->loadConfigFile($file);

            $definitionsData = $this->parseConfiguration($configData, $realPathName);

            foreach ($definitionsData as $definitionName => $definitionConfiguration) {
                // skip not used definitions
                if (null !== $usedDefinitions && !in_array($definitionName, $usedDefinitions, true)) {
                    continue;
                }

                $definitions[$definitionName] = $definitionConfiguration;
            }
        }

        return [self::ROOT_NODE_NAME => $definitions];
    }

    /**
     * @param array $directoriesWhiteList
     * @return Finder
     */
    protected function getConfigFinder(array $directoriesWhiteList = [])
    {
        $configDirectories = $this->getConfigDirectories($directoriesWhiteList);

        // prepare finder
        $finder = new Finder();
        $finder->in($configDirectories)->name($this->getConfigFilePattern());

        if ($directoriesWhiteList) {
            $finder->filter(
                function ($file) use ($directoriesWhiteList) {
                    foreach ($directoriesWhiteList as $allowedDirectory) {
                        if ($allowedDirectory &&
                            strpos($file, realpath($allowedDirectory) . DIRECTORY_SEPARATOR) === 0
                        ) {
                            return true;
                        }
                    }
                    return false;
                }
            );
        }

        return $finder;
    }

    /**
     * @return array
     */
    protected function getConfigDirectories()
    {
        $configDirectory = str_replace('/', DIRECTORY_SEPARATOR, $this->configDirectory);
        $configDirectories = array();

        foreach ($this->kernelBundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $bundleConfigDirectory = dirname($reflection->getFilename()) . $configDirectory;
            if (is_dir($bundleConfigDirectory) && is_readable($bundleConfigDirectory)) {
                $configDirectories[] = realpath($bundleConfigDirectory);
            }
        }

        return $configDirectories;
    }

    /**
     * Load config file and include imports.
     *
     * @param \SplFileInfo $file
     * @throws InvalidConfigurationException
     * @return array
     */
    protected function loadConfigFile(\SplFileInfo $file)
    {
        $realPathName = $file->getRealPath();
        $configData = Yaml::parse($realPathName);

        if (array_key_exists('imports', $configData) && is_array($configData['imports'])) {
            $imports = $configData['imports'];
            unset($configData['imports']);
            foreach ($imports as $importData) {
                if (array_key_exists('resource', $importData)) {
                    $resourceFile = new \SplFileInfo($file->getPath() . DIRECTORY_SEPARATOR . $importData['resource']);
                    if ($resourceFile->isReadable()) {
                        $includedData = $this->loadConfigFile($resourceFile);
                        $configData = array_merge_recursive($configData, $includedData);
                    } else {
                        throw new InvalidConfigurationException(
                            sprintf('Resource "%s" is unreadable', $resourceFile->getBasename())
                        );
                    }
                }
            }
        }

        return $configData;
    }

    /**
     * @param array $configuration
     * @param $fileName
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function parseConfiguration(array $configuration, $fileName)
    {
        try {
            $definitionsData = array();
            if (!empty($configuration[self::ROOT_NODE_NAME])) {
                $definitionsData = $this->definitionConfiguration->processConfiguration(
                    $configuration[self::ROOT_NODE_NAME]
                );
            }
        } catch (InvalidConfigurationException $exception) {
            $message = sprintf(
                'Can\'t parse process configuration from %s. %s',
                $fileName,
                $exception->getMessage()
            );
            throw new InvalidConfigurationException($message);
        }

        return $definitionsData;
    }

    /**
     * {@inheritDoc}
     */
    protected function getConfigFilePattern()
    {
        return $this->configFilePattern;
    }
}

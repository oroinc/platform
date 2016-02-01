<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Finder\Finder;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class PermissionConfigurationProvider
{
    const ROOT_NODE_NAME = 'permissions';

    /** @var PermissionDefinitionListConfiguration */
    protected $definitionConfiguration;

    /**
     * @param PermissionDefinitionListConfiguration $definitionConfiguration
     */
    public function __construct(PermissionDefinitionListConfiguration $definitionConfiguration)
    {
        $this->definitionConfiguration = $definitionConfiguration;
    }

    /**
     * @param array $usedDefinitions
     * @return array
     */
    public function getPermissionConfiguration(array $usedDefinitions = null)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_security',
            new YamlCumulativeFileLoader('Resources/config/permission.yml')
        );

        $definitions = [];

        $resources = $configLoader->load();

        foreach ($resources as $resource) {
            $definitionsData =  $this->parseConfiguration($resource->data, $resource->name);
            foreach ($definitionsData as $definitionName => $definitionConfiguration) {
                // skip not used definitions
                if ($usedDefinitions !== null && !in_array($definitionName, $usedDefinitions, true)) {
                    continue;
                }

                $definitions[$definitionName] = $definitionConfiguration;
            }
        }

        return [self::ROOT_NODE_NAME => $definitions];
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
                'Can\'t parse permission configuration from %s. %s',
                $fileName,
                $exception->getMessage()
            );
            throw new InvalidConfigurationException($message);
        }

        return $definitionsData;
    }
}

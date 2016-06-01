<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ProcessConfigurationProvider extends AbstractConfigurationProvider
{
    const NODE_DEFINITIONS = 'definitions';
    const NODE_TRIGGERS = 'triggers';

    /**
     * @var string
     */
    protected $configFilePattern = 'process.yml';

    /**
     * @var ProcessDefinitionListConfiguration
     */
    protected $definitionConfiguration;

    /**
     * @var ProcessTriggerListConfiguration
     */
    protected $triggerConfiguration;

    /**
     * @param array $kernelBundles
     * @param ProcessDefinitionListConfiguration $definitionConfiguration
     * @param ProcessTriggerListConfiguration $triggerConfiguration
     */
    public function __construct(
        array $kernelBundles,
        ProcessDefinitionListConfiguration $definitionConfiguration,
        ProcessTriggerListConfiguration $triggerConfiguration
    ) {
        parent::__construct($kernelBundles);

        $this->definitionConfiguration = $definitionConfiguration;
        $this->triggerConfiguration = $triggerConfiguration;
    }

    /**
     * @param array|null $usedDirectories
     * @param array|null $usedDefinitions
     * @return array
     * @throws InvalidConfigurationException
     */
    public function getProcessConfiguration(
        array $usedDirectories = null,
        array $usedDefinitions = null
    ) {
        $finder = $this->getConfigFinder((array)$usedDirectories);

        $definitions = array();
        $triggers = array();

        /** @var $file \SplFileInfo */
        foreach ($finder as $file) {
            $realPathName = $file->getRealPath();
            $configData = $this->loadConfigFile($file) ? : [];

            list($definitionsData, $triggersData) = $this->parseConfiguration($configData, $realPathName);

            foreach ($definitionsData as $definitionName => $definitionConfiguration) {
                // skip not used definitions
                if (null !== $usedDefinitions && !in_array($definitionName, $usedDefinitions)) {
                    continue;
                }

                $definitions[$definitionName] = $definitionConfiguration;
            }

            foreach ($triggersData as $definitionName => $triggersConfiguration) {
                // skip not used definitions
                if (null !== $usedDefinitions && !in_array($definitionName, $usedDefinitions)) {
                    continue;
                }

                if (!isset($triggers[$definitionName])) {
                    $triggers[$definitionName] = array();
                }

                $triggers[$definitionName] = array_merge($triggers[$definitionName], $triggersConfiguration);
            }
        }

        return array(
            self::NODE_DEFINITIONS => $definitions,
            self::NODE_TRIGGERS => $triggers
        );
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
            if (!empty($configuration[self::NODE_DEFINITIONS])) {
                $definitionsData = $this->definitionConfiguration->processConfiguration(
                    $configuration[self::NODE_DEFINITIONS]
                );
            }

            $triggersData = array();
            if (!empty($configuration[self::NODE_TRIGGERS])) {
                $triggersData = $this->triggerConfiguration->processConfiguration(
                    $configuration[self::NODE_TRIGGERS]
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

        return array($definitionsData, $triggersData);
    }

    /**
     * {@inheritDoc}
     */
    protected function getConfigFilePattern()
    {
        return $this->configFilePattern;
    }
}

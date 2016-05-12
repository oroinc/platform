<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class WorkflowConfigurationProvider extends AbstractConfigurationProvider
{
    /**
     * @var string
     */
    protected $configFilePattern = 'workflow.yml';

    /**
     * @var WorkflowListConfiguration
     */
    protected $configuration;

    /**
     * @param array $kernelBundles
     * @param WorkflowListConfiguration $configuration
     */
    public function __construct(array $kernelBundles, WorkflowListConfiguration $configuration)
    {
        parent::__construct($kernelBundles);

        $this->configuration = $configuration;
    }

    /**
     * @param array|null $usedDirectories
     * @param array|null $usedWorkflows
     * @return array
     * @throws InvalidConfigurationException
     */
    public function getWorkflowDefinitionConfiguration(
        array $usedDirectories = null,
        array $usedWorkflows = null
    ) {
        $finder = $this->getConfigFinder((array)$usedDirectories);

        $configuration = array();
        /** @var $file \SplFileInfo */
        foreach ($finder as $file) {
            $realPathName = $file->getRealPath();
            $configData = $this->loadConfigFile($file);

            try {
                $finalizedData = $this->configuration->processConfiguration($configData);
            } catch (InvalidConfigurationException $exception) {
                $message = sprintf(
                    'Can\'t parse workflow configuration from %s. %s',
                    $realPathName,
                    $exception->getMessage()
                );
                throw new InvalidConfigurationException($message);
            }

            foreach ($finalizedData as $workflowName => $workflowConfiguration) {
                // skip not used workflows
                if (null !== $usedWorkflows && !in_array($workflowName, $usedWorkflows)) {
                    continue;
                }

                if (isset($configuration[$workflowName])) {
                    throw new InvalidConfigurationException(
                        sprintf('Duplicated workflow name "%s" in %s', $workflowName, $realPathName)
                    );
                }

                $configuration[$workflowName] = $workflowConfiguration;
            }
        }

        return $configuration;
    }

    /**
     * {@inheritDoc}
     */
    protected function getConfigFilePattern()
    {
        return $this->configFilePattern;
    }
}

<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class WorkflowConfigurationProvider
{
    const NODE_WORKFLOWS = 'workflows';

    /**
     * @var string
     */
    protected $configDirectory = '/Resources/config/';

    /**
     * @var string
     */
    protected $configFilePattern = 'workflow.yml';

    /**
     * @var array
     */
    protected $kernelBundles = array();

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
        $this->kernelBundles = $kernelBundles;
        $this->configuration = $configuration;
    }

    /**
     * @param callable|null $filter
     * @return array
     * @throws InvalidConfigurationException
     */
    public function getWorkflowDefinitionConfiguration(\Closure $filter = null)
    {
        $configDirectories = $this->getConfigDirectories();

        $finder = new Finder();
        $finder->in($configDirectories)->name($this->configFilePattern);

        $configuration = array();
        /** @var $file \SplFileInfo */
        foreach ($finder as $file) {
            $realPathName = $file->getRealPath();
            if ($filter !== null && !$filter($realPathName)) {
                continue;
            }

            $configData = Yaml::parse($realPathName);

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
}

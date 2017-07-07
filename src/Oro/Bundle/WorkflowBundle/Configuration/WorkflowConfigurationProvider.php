<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Finder\Finder;

class WorkflowConfigurationProvider
{
    /** @var WorkflowListConfiguration */
    private $configuration;

    /** @var WorkflowConfigFinderBuilder */
    private $finderBuilder;

    /** @var ConfigFileReaderInterface */
    private $reader;

    /** @var WorkflowConfigurationImportsProcessor */
    private $importsProcessor;

    /**
     * @param WorkflowListConfiguration $configuration
     * @param WorkflowConfigFinderBuilder $finderBuilder
     * @param ConfigFileReaderInterface $reader
     * @param WorkflowConfigurationImportsProcessor $configurationImportsProcessor
     */
    public function __construct(
        WorkflowListConfiguration $configuration,
        WorkflowConfigFinderBuilder $finderBuilder,
        ConfigFileReaderInterface $reader,
        WorkflowConfigurationImportsProcessor $configurationImportsProcessor
    ) {
        $this->configuration = $configuration;
        $this->finderBuilder = $finderBuilder;
        $this->reader = $reader;
        $this->importsProcessor = $configurationImportsProcessor;
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
        $configs = [];

        foreach ($this->getConfigFiles((array)$usedDirectories) as $file) {
            $content = $this->importsProcessor->process($this->reader->read($file), $file);

            try {
                $finalizedData = $this->configuration->processConfiguration($content);
            } catch (InvalidConfigurationException $exception) {
                $message = sprintf(
                    'Can\'t parse workflow configuration from %s. %s',
                    $file->getRealPath(),
                    $exception->getMessage()
                );
                throw new InvalidConfigurationException($message);
            }

            foreach ($finalizedData as $workflowName => $workflowConfig) {
                if (null !== $usedWorkflows && !in_array($workflowName, $usedWorkflows, true)) {
                    continue;
                }

                if (!isset($configs[$workflowName])) {
                    $configs[$workflowName] = $workflowConfig;
                } else {
                    throw new InvalidConfigurationException(
                        sprintf('Duplicated workflow name "%s" in %s', $workflowName, $file->getRealPath())
                    );
                }
            }
        }

        return $configs;
    }

    /**
     * @param array $directories
     * @return Finder
     */
    private function getConfigFiles(array $directories): Finder
    {
        $finder = $this->finderBuilder->create();
        if ($directories) {
            $finder->filter(
                function ($file) use ($directories) {
                    foreach ($directories as $allowedDirectory) {
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
}

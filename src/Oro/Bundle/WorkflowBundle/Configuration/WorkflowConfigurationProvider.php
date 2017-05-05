<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\WorkflowBundle\Exception\WorkflowConfigurationImportException;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Yaml\Yaml;

class WorkflowConfigurationProvider extends AbstractConfigurationProvider
{
    /**
     * @var string
     */
    protected $configFilePattern = 'workflows.yml';

    /**
     * @var WorkflowListConfiguration
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $loadedRawConfigs = [];

    /**
     * @var array
     */
    protected $importWorkflowsInProgress = [];

    /**
     * @param array $kernelBundles
     * @param WorkflowListConfiguration $configuration
     */
    public function __construct(array $kernelBundles, WorkflowListConfiguration $configuration)
    {
        parent::__construct($kernelBundles);

        $this->configuration = $configuration;
        $this->configDirectory = '/Resources/config/oro/';
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

        $configuration = [];
        /** @var $file \SplFileInfo */
        foreach ($finder as $file) {
            $configData = $this->loadConfigFile($file);

            try {
                $finalizedData = $this->configuration->processConfiguration($configData);
            } catch (InvalidConfigurationException $exception) {
                $message = sprintf(
                    'Can\'t parse workflow configuration from %s. %s',
                    $file->getRealPath(),
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
                        sprintf('Duplicated workflow name "%s" in %s', $workflowName, $file->getRealPath())
                    );
                }

                $configuration[$workflowName] = $workflowConfiguration;
            }
        }

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadConfigFile(\SplFileInfo $file)
    {
        $realPathName = $file->getRealPath();
        $configData = Yaml::parse(file_get_contents($realPathName)) ?: [];

        if (array_key_exists('imports', $configData) && is_array($configData['imports'])) {
            $imports = $configData['imports'];
            unset($configData['imports']);
            $configData = $this->processImports($file, $imports, $configData);
        }

        $rootNode = WorkflowListConfiguration::NODE_WORKFLOWS;

        if (isset($configData[$rootNode]) && is_array($configData[$rootNode])) {
            foreach ($configData[$rootNode] as $name => $workflowRawConfig) {
                $this->loadedRawConfigs[$name] = $workflowRawConfig;
            }
        }

        return $configData;
    }

    /**
     * {@inheritdoc}
     */
    protected function processImports(\SplFileInfo $file, array $imports, array $configData)
    {
        //process workflow imports
        $workflowImports = [];
        foreach ($imports as $key => $importData) {
            if (isset($importData['workflow'])) {
                if (!isset($importData['as'], $importData['replace'])) {
                    throw new WorkflowConfigurationImportException(
                        sprintf(
                            '`workflow` type import directive options `as` and `replace` are required.' .
                            ' Given "%s" in `%s`.',
                            Yaml::dump([$importData], 1),
                            $file->getRealPath()
                        )
                    );
                }

                $source = [
                    'workflow' => $importData['workflow'],
                    'replace' => (array)$importData['replace'],
                ];

                if (isset($importData['resource'])) {
                    $resourceFile = new \SplFileInfo($file->getPath() . DIRECTORY_SEPARATOR . $importData['resource']);
                    $source['sources'] = new \ArrayIterator([$resourceFile]);
                } else {
                    $source['sources'] = $this->getConfigFinder()->filter(
                        function (\SplFileInfo $searchFile) use ($file) {
                            //skip current file as we already have its $configData
                            return $file->getRealPath() !== $searchFile->getRealPath();
                        }
                    );
                }

                //support multiple imports per recipient workflow
                $recipient = $importData['as'];
                if (!isset($workflowImports[$recipient])) {
                    $workflowImports[$recipient] = [$source];
                } else {
                    $workflowImports[$recipient][] = $source;
                }
                unset($imports[$key]);
            }
        }

        //process default file resources
        $configData = parent::processImports($file, $imports, $configData);

        foreach (array_keys($workflowImports) as $recipient) {
            $configData = $this->applyWorkflowImports($configData, $recipient, $workflowImports, $file);
        }

        return $configData;
    }

    /**
     * @param array $configData
     * @param $recipient
     * @param array|array[] $imports
     * @param \SplFileInfo $sourceFile
     * @return array
     */
    protected function applyWorkflowImports(array $configData, $recipient, array &$imports, \SplFileInfo $sourceFile)
    {
        //if was already processed
        if (!isset($imports[$recipient])) {
            return $configData;
        }

        foreach ($imports[$recipient] as $source) {
            $sourceWorkflow = $source['workflow'];

            if (isset($this->importWorkflowsInProgress[$sourceWorkflow])) {
                list($importFilePath, $importRecipient) = $this->importWorkflowsInProgress[$sourceWorkflow];
                throw new \LogicException(
                    sprintf(
                        'Recursion met. File `%s` tries to import workflow `%s`' .
                        ' for `%s` that imports it too in `%s`',
                        $sourceFile->getRealPath(),
                        $sourceWorkflow,
                        $importRecipient,
                        $importFilePath
                    )
                );
            }

            $this->importWorkflowsInProgress[$sourceWorkflow] = [$sourceFile->getRealPath(), $recipient];

            try {
                //same file import
                if (isset($configData[WorkflowListConfiguration::NODE_WORKFLOWS][$sourceWorkflow])) {
                    //if same file import has own import
                    if (isset($imports[$sourceWorkflow])) {
                        $configData = $this->applyWorkflowImports($configData, $sourceWorkflow, $imports, $sourceFile);
                    }
                    $sourceConfig = $configData[WorkflowListConfiguration::NODE_WORKFLOWS][$sourceWorkflow];
                } else {
                    $sourceConfig = $this->getSourceConfig($sourceWorkflow, $source['sources']);
                }
            } catch (WorkflowConfigurationImportException $exception) {
                if ($exception->getPrevious()) {
                    //deep import exception should have `previous` already defined throwing as is
                    throw $exception;
                }
                throw new WorkflowConfigurationImportException(
                    sprintf(
                        'Error occurs while importing workflow for `%s`. Error: "%s" in `%s`',
                        $recipient,
                        $exception->getMessage(),
                        $sourceFile->getRealPath()
                    ),
                    $exception
                );
            }

            unset($this->importWorkflowsInProgress[$sourceWorkflow]);

            foreach ((array)$source['replace'] as $path) {
                $sourceConfig = ArrayUtil::unsetPath($sourceConfig, explode('.', $path));
            }

            $configData = ArrayUtil::arrayMergeRecursiveDistinct(
                [WorkflowListConfiguration::NODE_WORKFLOWS => [$recipient => $sourceConfig]],
                $configData
            );
        }
        unset($imports[$recipient]);

        return $configData;
    }

    /**
     * @param string $sourceName
     * @param \Traversable|\SplFileInfo[] $sources
     * @return array
     */
    private function getSourceConfig($sourceName, \Traversable $sources)
    {
        if (!isset($this->loadedRawConfigs[$sourceName])) {
            foreach ($sources as $file) {
                $this->loadConfigFile($file);
            }
        }

        if (!isset($this->loadedRawConfigs[$sourceName])) {
            throw new WorkflowConfigurationImportException(
                sprintf(
                    'Can not find workflow `%s` for import.`',
                    $sourceName
                )
            );
        }

        return $this->loadedRawConfigs[$sourceName];
    }

    /**
     * {@inheritDoc}
     */
    protected function getConfigFilePattern()
    {
        return $this->configFilePattern;
    }
}

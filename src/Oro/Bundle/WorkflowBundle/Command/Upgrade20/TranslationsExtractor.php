<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Yaml\Yaml;

class TranslationsExtractor
{
    /** @var TranslationFile[] */
    private $translationFiles = [];

    /** @var ResourceTranslationGenerator[] */
    private $translationGenerators = [];

    /** @var callable */
    private $resourceUpdater;

    /** @var MovementOptions */
    private $options;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param MovementOptions $movementOptions
     */
    public function __construct(MovementOptions $movementOptions, LoggerInterface $logger = null)
    {
        $this->options = $movementOptions;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @return \Generator|ConfigFile[]
     */
    private function configFiles()
    {
        foreach ($this->options->getBundles() as $bundle) {
            $configFile = $bundle->getPath() . DIRECTORY_SEPARATOR . ltrim($this->options->getConfigFilePath());
            if (\file_exists($configFile)) {
                $this->logger->info('Found configuration file {file}.', ['file' => $configFile]);
                yield new ConfigFile($configFile, $bundle);
            }
        }
    }

    /**
     * @param ConfigFile $configFile
     * @return TranslationFile
     */
    private function getTranslationFile(ConfigFile $configFile)
    {
        if (isset($this->translationFiles[$configFile->getRealPath()])) {
            return $this->translationFiles[$configFile->getRealPath()];
        }

        return $this->translationFiles[$configFile->getRealPath()] = new TranslationFile(
            $configFile->getBundle()->getPath()
            . DIRECTORY_SEPARATOR
            . $this->options->getTranslationFilePath()
        );
    }

    /**
     * @param bool $dumpFlatKeys
     */
    public function execute($dumpFlatKeys = true)
    {
        foreach ($this->configFiles() as $configFile) {
            $transFile = $this->getTranslationFile($configFile);
            $this->logger->debug('Loading configuration file {file}', ['file' => $configFile->getRealPath()]);
            foreach ($this->loadConfigFile($configFile->getFileInfo()) as $configResource) {
                $this->logger->debug(
                    'Loaded resource {resource}. Processing.',
                    ['resource', $configResource->getFile()->getRealPath()]
                );
                $this->processConfigResource($configResource, $transFile);
                $configResource->dump();
            }

            $transFile->dump($dumpFlatKeys);
        }
    }

    private function processConfigResource(ConfigResource $configResource, TranslationFile $translationFile)
    {
        foreach ($this->translationGenerators as $processor) {
            foreach ($processor->generate($configResource) as $data) {
                $this->logger->debug(
                    'Generated key {key} with value {value}. Keep as translation.',
                    ['key' => $data->getKey(), 'value' => $data->getValue(), 'path' => $data->getPath()]
                );
                $translationFile->addTranslation($data->getKey(), $data->getValue());
                $this->logger->debug(
                    'Updating config resource {resource} by data {data}',
                    ['resource' => $configResource->getFile()->getRealPath(), 'data' => $data]
                );
                call_user_func($this->resourceUpdater, $configResource, $data);
            }
        }
    }

    /**
     * @param \SplFileInfo $file
     * @return \Generator|ConfigResource[]
     */
    private function loadConfigFile(\SplFileInfo $file)
    {
        $realPathName = $file->getRealPath();
        $configData = Yaml::parse(file_get_contents($realPathName)) ?: [];

        if (array_key_exists('imports', $configData) && is_array($configData['imports'])) {
            $imports = $configData['imports'];
            unset($configData['imports']);

            foreach ($imports as $importData) {
                if (array_key_exists('resource', $importData)) {
                    $resourceFile = new \SplFileInfo($file->getPath() . DIRECTORY_SEPARATOR . $importData['resource']);
                    if ($resourceFile->isReadable()) {
                        foreach ($this->loadConfigFile($resourceFile) as $configResource) {
                            yield $configResource;
                        }
                    }
                }
            }
        }

        yield new ConfigResource($file, $configData);
    }

    /**
     * @param ResourceTranslationGenerator $generator
     */
    public function addResourceTranslationGenerator(ResourceTranslationGenerator $generator)
    {
        $this->translationGenerators[] = $generator;
    }

    public function setResourceUpdater(callable $updater)
    {
        $this->resourceUpdater = $updater;
    }
}

<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

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

    /**
     * @param MovementOptions $movementOptions
     */
    public function __construct(MovementOptions $movementOptions)
    {
        $this->options = $movementOptions;
    }

    /**
     * @return \Generator|ConfigFile[]
     */
    private function configFiles()
    {
        foreach ($this->options->getBundles() as $bundle) {
            $configFile = $bundle->getPath() . DIRECTORY_SEPARATOR . ltrim($this->options->getConfigFilePath());

            if (\file_exists($configFile)) {
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

            foreach ($this->loadConfigFile($configFile->getFileInfo()) as $configResource) {
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
                //echo "{$data->getKey()} => {$data->getValue()}" . PHP_EOL;
                $translationFile->addTranslation($data->getKey(), $data->getValue());
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

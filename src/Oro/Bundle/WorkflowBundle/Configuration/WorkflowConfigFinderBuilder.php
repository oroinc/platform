<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Finder\Finder;

/**
 * Workflows config resources Finder builder.
 */
class WorkflowConfigFinderBuilder
{
    private ConfigFinderFactory $configFinderFactory;
    private ?string $fileName = null;
    private ?string $subDirectory = null;
    private ?string $appSubDirectory = null;

    public function __construct(ConfigFinderFactory $configFinderFactory)
    {
        $this->configFinderFactory = $configFinderFactory;
    }

    /**
     * @throws \BadMethodCallException
     */
    public function create(): Finder
    {
        if (null === $this->subDirectory) {
            throw $this->notConfiguredException('subDirectory');
        }
        if (null === $this->fileName) {
            throw $this->notConfiguredException('fileName');
        }
        if (null === $this->appSubDirectory) {
            throw $this->notConfiguredException('appSubDirectory');
        }

        return $this->configFinderFactory->create(
            $this->subDirectory,
            $this->appSubDirectory,
            $this->fileName
        );
    }

    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }

    public function setSubDirectory(string $subDirectory)
    {
        $this->subDirectory = $subDirectory;
    }

    public function setAppSubDirectory(string $subDirectory): void
    {
        $this->appSubDirectory = $subDirectory;
    }

    private function notConfiguredException(string $propertyMissed): \BadMethodCallException
    {
        return new \BadMethodCallException(
            sprintf('Can not create finder. Not properly configured. No %s specified.', $propertyMissed)
        );
    }
}

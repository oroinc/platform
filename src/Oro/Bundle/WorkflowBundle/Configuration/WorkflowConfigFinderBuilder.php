<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Symfony\Component\Finder\Finder;

class WorkflowConfigFinderBuilder
{
    /** @var ConfigFinderFactory */
    private $configFinderFactory;

    /** @var string */
    private $fileName;

    /** @var string */
    private $subDirectory;

    /**
     * @param ConfigFinderFactory $configFinderFactory
     */
    public function __construct(ConfigFinderFactory $configFinderFactory)
    {
        $this->configFinderFactory = $configFinderFactory;
    }

    /**
     * @return Finder
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

        return $this->configFinderFactory->create($this->subDirectory, $this->fileName);
    }

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @param string $subDirectory
     */
    public function setSubDirectory(string $subDirectory)
    {
        $this->subDirectory = $subDirectory;
    }

    /**
     * @param string $propertyMissed
     * @return \BadMethodCallException
     */
    private function notConfiguredException(string $propertyMissed): \BadMethodCallException
    {
        return new \BadMethodCallException(
            sprintf('Can not create finder. Not properly configured. No %s specified.', $propertyMissed)
        );
    }
}

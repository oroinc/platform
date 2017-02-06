<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Nelmio\Alice\Fixtures\Loader as AliceLoader;
use Nelmio\Alice\Instances\Collection as AliceReferenceRepository;
use Nelmio\Alice\Instances\Processor\Processor;
use Symfony\Component\Config\FileLocator;

class AliceFixtureLoader extends AliceLoader
{
    /**
     * @var FileLocator
     */
    protected $fileLocator;

    /**
     * {@inheritdoc}
     */
    public function __construct($locale = 'en_US', array $providers = [], $seed = 1, array $parameters = [])
    {
        parent::__construct($locale, $providers, $seed, $parameters);
        $this->addParser(new AliceYamlParser($this));
    }

    /**
     * @return AliceReferenceRepository
     */
    public function getReferenceRepository()
    {
        return $this->objects;
    }

    /**
     * {@inheritdoc}
     */
    public function load($dataOrFilename)
    {
        if (is_string($dataOrFilename)) {
            $dataOrFilename = $this->locateFile($dataOrFilename);
        }

        return parent::load($dataOrFilename);
    }

    /**
     * @param  string $file
     * @return string Full path to file
     */
    public function locateFile($file)
    {
        return $this->fileLocator->locate($file);
    }

    /**
     * @param FileLocator $fileLocator
     */
    public function setFileLocator(FileLocator $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * @return Processor
     */
    public function getProcessor()
    {
        return $this->processor;
    }
}

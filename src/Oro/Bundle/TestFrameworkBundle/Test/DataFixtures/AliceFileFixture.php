<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Expanded Alice file fixture. Added 'dependencies' and 'initial' parameters.
 */
class AliceFileFixture extends AliceFixture implements DependentFixtureInterface
{
    /** @var string */
    private $fileName;

    /**
     * @param string $fileName
     */
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        $data = Yaml::parse(file_get_contents($this->loader->locateFile($this->fileName)));

        if (isset($data['dependencies'])) {
            return $data['dependencies'];
        }

        return [];
    }

    /**
     * If a nelmio/alice file fixture have 'initial' parameter with 'true' value
     * will not clear the entity manager after this fixture.
     * This might be helpful if you need to load existing data to reference in nelmio/alice file.
     *
     * @see \Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\DataFixturesExecutor
     *
     * @return bool
     */
    public function isInitialFixture()
    {
        $data = Yaml::parse(file_get_contents($this->loader->locateFile($this->fileName)));

        if (isset($data['initial'])) {
            return $data['initial'];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadData()
    {
        return $this->loader->load($this->fileName);
    }
}

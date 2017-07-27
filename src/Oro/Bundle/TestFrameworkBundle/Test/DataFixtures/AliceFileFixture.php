<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\Yaml\Yaml;

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
        $data = Yaml::parse($this->loader->locateFile($this->fileName));

        if (isset($data['dependencies'])) {
            return $data['dependencies'];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function loadData()
    {
        return $this->loader->load($this->fileName);
    }
}

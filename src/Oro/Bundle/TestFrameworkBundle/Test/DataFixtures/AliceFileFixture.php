<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\Yaml\Yaml;

class AliceFileFixture extends AliceFixture implements DependentFixtureInterface
{
    /** @var string */
    private $fileName;

    /** @var mixed */
    private $data = false;

    /** @var string[] */
    private $dependencies;

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
    protected function loadData()
    {
        $this->ensureInitialized();

        return $this->loader->load(null !== $this->data ? $this->data : $this->fileName);
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        $this->ensureInitialized();

        return $this->dependencies;
    }

    private function ensureInitialized()
    {
        if (false !== $this->data) {
            return;
        }

        $this->dependencies = [];
        if (1 === preg_match('/\.ya?ml$/', $this->fileName)) {
            $this->data = Yaml::parse($this->fileName);
            if (array_key_exists('dependencies', $this->data)) {
                $this->dependencies = $this->data['dependencies'];
                unset($this->data['dependencies']);
            }
        } else {
            $this->data = null;
        }
    }
}

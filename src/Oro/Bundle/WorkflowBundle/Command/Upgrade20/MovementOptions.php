<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class MovementOptions
{
    /** @var array|BundleInterface[] */
    private $bundles = [];

    /** @var string */
    private $configFilePath;

    /** @var string */
    private $translationFilePath;

    /**
     * @return array|BundleInterface[]
     */
    public function getBundles()
    {
        return $this->bundles;
    }

    /**
     * @param array $bundles
     * @return $this
     */
    public function setBundles(array $bundles)
    {
        $this->bundles = $bundles;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigFilePath()
    {
        return $this->configFilePath;
    }

    /**
     * @param string $configFilePath
     * @return $this
     */
    public function setConfigFilePath($configFilePath)
    {
        $this->configFilePath = $configFilePath;

        return $this;
    }

    /**
     * @return string
     */
    public function getTranslationFilePath()
    {
        return $this->translationFilePath;
    }

    /**
     * @param string $translationFilePath
     */
    public function setTranslationFilePath($translationFilePath)
    {
        $this->translationFilePath = $translationFilePath;
    }
}

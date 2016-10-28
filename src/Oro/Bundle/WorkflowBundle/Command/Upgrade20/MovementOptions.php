<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class MovementOptions
{
    /**
     * @var array|BundleInterface[]
     */
    private $bundles = [];

    private $configFilePath;

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
     * @return mixed
     */
    public function getConfigFilePath()
    {
        return $this->configFilePath;
    }

    /**
     * @param mixed $configFilePath
     * @return $this
     */
    public function setConfigFilePath($configFilePath)
    {
        $this->configFilePath = $configFilePath;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTranslationFilePath()
    {
        return $this->translationFilePath;
    }

    /**
     * @param mixed $translationFilePath
     */
    public function setTranslationFilePath($translationFilePath)
    {
        $this->translationFilePath = $translationFilePath;
    }
}

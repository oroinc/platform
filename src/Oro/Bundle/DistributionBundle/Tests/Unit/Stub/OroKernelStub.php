<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Stub;

use Symfony\Component\Config\Loader\LoaderInterface;

use Oro\Bundle\DistributionBundle\OroKernel;

class OroKernelStub extends OroKernel
{
    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        throw new \BadMethodCallException();
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;
    }

    /**
     * {@inheritdoc}
     */
    protected function findBundles($roots = [])
    {
        return [
            $this->getRootDir() . 'bundles1.yml',
            $this->getRootDir() . 'bundles2.yml',
            $this->getRootDir() . 'bundles3.yml',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return $this->collectBundles();
    }

    /**
     * @param array $bundleMap
     */
    public function setBundleMap(array $bundleMap)
    {
        $this->bundleMap = $bundleMap;
    }
}

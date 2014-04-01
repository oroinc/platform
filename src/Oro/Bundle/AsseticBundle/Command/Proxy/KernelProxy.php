<?php

namespace Oro\Bundle\AsseticBundle\Command\Proxy;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelProxy implements KernelInterface
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var array
     */
    protected $excludeBundleNames = [];

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Adds a bundle to a exclude list
     *
     * @param string $bundleName
     */
    public function excludeBundle($bundleName)
    {
        $this->excludeBundleNames[] = $bundleName;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return $this->kernel->registerBundles();
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $this->kernel->registerContainerConfiguration($loader);
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->kernel->boot();
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        $this->kernel->shutdown();
    }

    /**
     * {@inheritdoc}
     */
    public function getBundles()
    {
        $bundles = $this->kernel->getBundles();
        if (!empty($this->excludeBundleNames)) {
            foreach ($bundles as $key => $bundle) {
                if (in_array($bundle->getName(), $this->excludeBundleNames)) {
                    unset($bundles[$key]);
                }
            }
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function isClassInActiveBundle($class)
    {
        return $this->kernel->isClassInActiveBundle($class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBundle($name, $first = true)
    {
        if (!empty($this->excludeBundleNames) && in_array($name, $this->excludeBundleNames)) {
            throw new \InvalidArgumentException(sprintf('Bundle "%s" is in exclude list.', $name));
        }

        return $this->kernel->getBundle($name, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function locateResource($name, $dir = null, $first = true)
    {
        return $this->kernel->locateResource($name, $dir, $first);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->kernel->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment()
    {
        return $this->kernel->getEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug()
    {
        return $this->kernel->isDebug();
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        return $this->kernel->getRootDir();
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return $this->kernel->getContainer();
    }

    /**
     * {@inheritdoc}
     */
    public function getStartTime()
    {
        return $this->kernel->getStartTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return $this->kernel->getCacheDir();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->kernel->getLogDir();
    }

    /**
     * {@inheritdoc}
     */
    public function getCharset()
    {
        return $this->kernel->getCharset();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        return $this->kernel->handle($request, $type, $catch);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return $this->kernel->serialize();
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->kernel->unserialize($serialized);
    }
}

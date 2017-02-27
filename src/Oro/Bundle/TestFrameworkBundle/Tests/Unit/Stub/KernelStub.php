<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelStub implements KernelInterface
{
    protected $bundleMap;

    protected $container;

    protected $parameters = [
        'database_driver' => 'pdo_mysql',
        'database_host' => '127.0.0.1',
        'database_port' => null,
        'database_name' => 'oro_crm',
        'database_user' => 'root',
        'database_password' => null,
        'session_handler' => 'session.handler.native_file',
        'message_queue_transport' => 'dbal',
        'message_queue_transport_config' => null,
    ];

    public function __construct()
    {
        $this->container = new Container();

        foreach ($this->parameters as $key => $value) {
            $this->container->setParameter($key, $value);
        }
    }

    /**
     * @param array $bundleMap
     */
    public function setBundleMap(array $bundleMap)
    {
        $this->bundleMap = $bundleMap;
    }

    /**
     * {@inheritdoc}
     */
    public function getBundle($name, $first = true)
    {
        return $this->bundleMap[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getBundles()
    {
        return $this->bundleMap;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isClassInActiveBundle($class)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function locateResource($name, $dir = null, $first = true)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function getStartTime()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getCharset()
    {
    }
}

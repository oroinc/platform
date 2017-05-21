<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub;

use Oro\Bundle\DistributionBundle\OroKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelStub extends OroKernel implements KernelInterface
{
    protected $bundleMap;

    protected $container;

    /**
     * @var array
     */
    protected $registeredBundles = [];

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

    /**
     * KernelStub constructor.
     * @param array $bundleConfig In format
     * [
     *   [name => Bundle1, path => /var/www/app],
     *   [name => Bundle2, parent => Bundle1]
     * ]
     */
    public function __construct(array $bundleConfig = [])
    {
        $this->container = new Container();

        foreach ($this->parameters as $key => $value) {
            $this->container->setParameter($key, $value);
        }

        foreach ($bundleConfig as $config) {
            $bundle = new TestBundle($config['name']);

            if (array_key_exists('parent', $config)) {
                $bundle->setParent($config['parent']);
            }

            if (array_key_exists('path', $config)) {
                $bundle->setPath($config['path']);
            }

            $this->registeredBundles[] = $bundle;
        }

        $this->initializeBundles();
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
    public function registerBundles()
    {
        return $this->registeredBundles;
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

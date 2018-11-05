<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub;

use Oro\Bundle\DistributionBundle\OroKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

class KernelStub extends OroKernel
{
    /** @var string */
    protected $logDir;

    /** @var array */
    protected $registeredBundles = [];

    /** @var array */
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
     * @param string $logDir
     * @param array  $bundleConfig [[name => Bundle1, path => /var/www/app], ...]
     */
    public function __construct(string $logDir, array $bundleConfig = [])
    {
        $this->logDir = $logDir;
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
        return $this->logDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getCharset()
    {
    }
}

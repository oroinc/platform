<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Stub;

use Oro\Bundle\DistributionBundle\OroKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class KernelStub extends OroKernel
{
    /** @var string */
    protected $logDir;

    /** @var array */
    protected $registeredBundles = [];

    /** @var array */
    protected $parameters = [
        'database_dsn' => 'postgresql://root@127.0.0.1/oro_crm',
        'session_handler' => 'session.handler.native_file',
        'message_queue_transport_dsn' => 'dbal:',
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

            if (array_key_exists('path', $config)) {
                $bundle->setPath($config['path']);
            }

            $this->registeredBundles[] = $bundle;
        }

        $this->initializeBundles();
    }

    public function setBundleMap(array $bundleMap)
    {
        $this->bundleMap = $bundleMap;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles(): iterable
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
    public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = true): Response
    {
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
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
    public function locateResource($name): string
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
    public function getEnvironment(): string
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug(): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getStartTime(): float
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return 'var/cache';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir(): string
    {
        return $this->logDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getCharset(): string
    {
    }
}

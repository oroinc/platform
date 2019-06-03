<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Cache\ClearableCache;
use Oro\Bundle\CacheBundle\Provider\DirectoryAwareFileCacheInterface;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;
use Oro\Bundle\EntityExtendBundle\Extend\EntityProxyGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Symfony\Bundle\FrameworkBundle\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Handles warmupping cache methods
 */
abstract class CacheCommand extends Command
{
    /** @var string|null */
    protected $cacheDir;

    /** @var EntityProxyGenerator */
    private $entityProxyGenerator;

    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    /** @var ExtendConfigDumper */
    protected $extendConfigDumper;

    /** var Registry **/
    private $doctrine;

    /** @var KernelInterface */
    private $kernel;

    /**
     * @param EntityProxyGenerator $entityProxyGenerator
     * @param EntityAliasResolver $entityAliasResolver
     * @param ExtendConfigDumper $extendConfigDumper
     * @param Registry $doctrine
     * @param KernelInterface $kernel
     */
    public function __construct(
        EntityProxyGenerator $entityProxyGenerator,
        EntityAliasResolver $entityAliasResolver,
        ExtendConfigDumper $extendConfigDumper,
        Registry $doctrine,
        KernelInterface $kernel
    ) {
        $this->entityProxyGenerator = $entityProxyGenerator;
        $this->entityAliasResolver = $entityAliasResolver;
        $this->extendConfigDumper = $extendConfigDumper;
        $this->doctrine = $doctrine;
        $this->kernel = $kernel;
        parent::__construct();
    }

    /**
     * @return KernelInterface
     */
    protected function getKernel()
    {
        $application = $this->getApplication();

        return $application instanceof ConsoleApplication
            ? $application->getKernel()
            : $this->kernel;
    }

    /**
     * Warms up caches which may be affected by extended entities
     *
     * @param OutputInterface $output
     */
    protected function warmup(OutputInterface $output)
    {
        $callable = function () use ($output) {
            $this->warmupExtendedEntityCache($output);
            // Doctrine metadata, proxies and dependent caches might be invalid after extended entities cache generation
            $this->warmupMetadataCache($output);
            $this->warmupProxies($output);
            $this->warmupEntityAliasesCache($output);
        };

        SafeDatabaseChecker::safeDatabaseCallable($callable);
    }

    /**
     * Warms up extended entities cache
     *
     * @param OutputInterface $output
     */
    protected function warmupExtendedEntityCache(OutputInterface $output)
    {
        $output->writeln('Dump the configuration of extended entities to the cache');

        $cacheDir = $this->extendConfigDumper->getCacheDir();
        if (empty($this->cacheDir) || $this->cacheDir === $cacheDir) {
            $this->extendConfigDumper->dump();
            $this->setClassAliases($cacheDir);
        } else {
            $this->extendConfigDumper->setCacheDir($this->cacheDir);
            try {
                $this->extendConfigDumper->dump();
                $this->extendConfigDumper->setCacheDir($cacheDir);
            } catch (\Exception $e) {
                $this->extendConfigDumper->setCacheDir($cacheDir);
                throw $e;
            }
            $this->setClassAliases($this->cacheDir);
        }
    }

    /**
     * Warms up Doctrine metadata cache
     *
     * @param OutputInterface $output
     */
    protected function warmupMetadataCache(OutputInterface $output)
    {
        $kernel              = $this->getKernel();
        $em                  = $this->doctrine->getManager();
        $metadataCacheDriver = $em->getConfiguration()->getMetadataCacheImpl();

        if (!$metadataCacheDriver instanceof ClearableCache) {
            return;
        }

        if (empty($this->cacheDir) || $this->cacheDir === $kernel->getCacheDir()) {
            $output->writeln('Clear entity metadata cache');
            $metadataCacheDriver->deleteAll();
            $output->writeln('Warm up entity metadata cache');
            $em->getMetadataFactory()->getAllMetadata();
        } else {
            if (!$metadataCacheDriver instanceof DirectoryAwareFileCacheInterface) {
                return;
            }

            $kernelCacheDir   = $kernel->getCacheDir();
            $metadataCacheDir = $metadataCacheDriver->getDirectory();
            if (strpos($metadataCacheDir, $kernelCacheDir) !== 0) {
                return;
            }

            $metadataCacheDriver->setDirectory($this->cacheDir . substr($metadataCacheDir, strlen($kernelCacheDir)));
            try {
                $output->writeln('Clear entity metadata cache');
                $metadataCacheDriver->deleteAll();
                $output->writeln('Warm up entity metadata cache');
                $em->getMetadataFactory()->getAllMetadata();
                $metadataCacheDriver->setDirectory($metadataCacheDir);
            } catch (\Exception $e) {
                $metadataCacheDriver->setDirectory($metadataCacheDir);
                throw $e;
            }
        }
    }

    /**
     * Generates Doctrine proxy classes for extended entities
     *
     * @param OutputInterface $output
     */
    protected function warmupProxies(OutputInterface $output)
    {
        $em = $this->doctrine->getManager();
        if ($em->getConfiguration()->getAutoGenerateProxyClasses()) {
            return;
        }

        $output->writeln('Generate Doctrine proxy classes for extended entities');

        $cacheDir = $this->entityProxyGenerator->getCacheDir();
        if (empty($this->cacheDir) || $this->cacheDir === $cacheDir) {
            $this->entityProxyGenerator->generateProxies();
        } else {
            $this->entityProxyGenerator->setCacheDir($this->cacheDir);
            try {
                $this->entityProxyGenerator->generateProxies();
                $this->entityProxyGenerator->setCacheDir($cacheDir);
            } catch (\Exception $e) {
                $this->entityProxyGenerator->setCacheDir($cacheDir);
                throw $e;
            }
        }
    }

    /**
     * Warms up entity aliases cache
     *
     * @param OutputInterface $output
     */
    protected function warmupEntityAliasesCache(OutputInterface $output)
    {
        $output->writeln('Warm up entity aliases cache');
        $this->entityAliasResolver->warmUpCache();
    }

    /**
     * Sets class aliases for extended entities.
     *
     * @param string $cacheDir The cache directory
     */
    protected function setClassAliases($cacheDir)
    {
        ExtendClassLoadingUtils::setAliases($cacheDir);
    }
}

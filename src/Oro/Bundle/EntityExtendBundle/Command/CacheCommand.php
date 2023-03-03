<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CacheBundle\Provider\DirectoryAwareFileCacheInterface;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;
use Oro\Bundle\EntityExtendBundle\Extend\EntityProxyGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Symfony\Bundle\FrameworkBundle\Console\Application as ConsoleApplication;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Base class for various extended entity cache warmup commands.
 */
abstract class CacheCommand extends Command
{
    protected ?string $cacheDir;

    private EntityProxyGenerator $entityProxyGenerator;
    private EntityAliasResolver $entityAliasResolver;
    protected ExtendConfigDumper $extendConfigDumper;
    private ManagerRegistry $doctrine;
    private KernelInterface $kernel;

    public function __construct(
        EntityProxyGenerator $entityProxyGenerator,
        EntityAliasResolver $entityAliasResolver,
        ExtendConfigDumper $extendConfigDumper,
        ManagerRegistry $doctrine,
        KernelInterface $kernel
    ) {
        $this->entityProxyGenerator = $entityProxyGenerator;
        $this->entityAliasResolver = $entityAliasResolver;
        $this->extendConfigDumper = $extendConfigDumper;
        $this->doctrine = $doctrine;
        $this->kernel = $kernel;
        parent::__construct();
    }

    protected function getKernel(): KernelInterface
    {
        $application = $this->getApplication();

        return $application instanceof ConsoleApplication
            ? $application->getKernel()
            : $this->kernel;
    }

    /**
     * Warms up caches which may be affected by extended entities
     */
    protected function warmup(OutputInterface $output): void
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
     * @throws \Exception
     */
    protected function warmupExtendedEntityCache(OutputInterface $output): void
    {
        $output->writeln('Dump the configuration of extended entities to the cache');
        $this->extendConfigDumper->validateExtendEntityConfig();

        $cacheDir = $this->extendConfigDumper->getCacheDir();
        if (empty($this->cacheDir) || $this->cacheDir === $cacheDir) {
            $this->extendConfigDumper->dump();
        } else {
            $this->extendConfigDumper->setCacheDir($this->cacheDir);
            try {
                $this->extendConfigDumper->dump();
                $this->extendConfigDumper->setCacheDir($cacheDir);
            } catch (\Exception $e) {
                $this->extendConfigDumper->setCacheDir($cacheDir);
                throw $e;
            }
        }
    }

    /**
     * Warms up Doctrine metadata cache
     * @throws \Exception
     */
    protected function warmupMetadataCache(OutputInterface $output): void
    {
        $kernel              = $this->getKernel();
        $em                  = $this->doctrine->getManager();
        $metadataCacheDriver = $em->getConfiguration()->getMetadataCache();

        if (!$metadataCacheDriver instanceof AdapterInterface) {
            return;
        }

        if (empty($this->cacheDir) || $this->cacheDir === $kernel->getCacheDir()) {
            $output->writeln('Clear entity metadata cache');
            $metadataCacheDriver->clear();
            $output->writeln('Warm up entity metadata cache');
            $em->getMetadataFactory()->getAllMetadata();
        } else {
            if (!$metadataCacheDriver instanceof DirectoryAwareFileCacheInterface) {
                return;
            }

            $kernelCacheDir   = $kernel->getCacheDir();
            $metadataCacheDir = $metadataCacheDriver->getDirectory();
            if (!str_starts_with($metadataCacheDir, $kernelCacheDir)) {
                return;
            }

            $metadataCacheDriver->setDirectory($this->cacheDir . substr($metadataCacheDir, strlen($kernelCacheDir)));
            try {
                $output->writeln('Clear entity metadata cache');
                $metadataCacheDriver->clear();
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
     * @throws \Exception
     */
    protected function warmupProxies(OutputInterface $output): void
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
     */
    protected function warmupEntityAliasesCache(OutputInterface $output): void
    {
        $output->writeln('Warm up entity aliases cache');
        $this->entityAliasResolver->warmUpCache();
    }
}

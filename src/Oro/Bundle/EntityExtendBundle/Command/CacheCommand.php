<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Doctrine\Common\Cache\ClearableCache;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application as ConsoleApplication;

use Oro\Bundle\CacheBundle\Provider\DirectoryAwareFileCacheInterface;
use Oro\Bundle\EntityExtendBundle\Extend\EntityProxyGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

abstract class CacheCommand extends ContainerAwareCommand
{
    /** @var string|null */
    protected $cacheDir;

    /**
     * @return ExtendConfigDumper
     */
    protected function getExtendConfigDumper()
    {
        return $this->getContainer()->get('oro_entity_extend.tools.dumper');
    }

    /**
     * @return KernelInterface
     */
    protected function getKernel()
    {
        $application = $this->getApplication();

        return $application instanceof ConsoleApplication
            ? $application->getKernel()
            : $this->getContainer()->get('kernel');
    }

    /**
     * Warms up caches which may be affected by extended entities
     *
     * @param OutputInterface $output
     */
    protected function warmup(OutputInterface $output)
    {
        $this->warmupExtendedEntityCache($output);
        // Doctrine metadata and proxies might be invalid after extended entities cache generation
        $this->warmupMetadataCache($output);
        $this->warmupProxies($output);
    }

    /**
     * Warms up extended entities cache
     *
     * @param OutputInterface $output
     */
    protected function warmupExtendedEntityCache(OutputInterface $output)
    {
        $output->writeln('Dump the configuration of extended entities to the cache');
        $dumper = $this->getExtendConfigDumper();

        $cacheDir = $dumper->getCacheDir();
        if (empty($this->cacheDir) || $this->cacheDir === $cacheDir) {
            $dumper->dump();
            $this->setClassAliases($cacheDir, true);
        } else {
            $dumper->setCacheDir($this->cacheDir);
            try {
                $dumper->dump();
                $dumper->setCacheDir($cacheDir);
            } catch (\Exception $e) {
                $dumper->setCacheDir($cacheDir);
                throw $e;
            }
            $this->setClassAliases($this->cacheDir, true);
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
        $em                  = $kernel->getContainer()->get('doctrine')->getManager();
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
        $em = $this->getKernel()->getContainer()->get('doctrine')->getManager();
        if ($em->getConfiguration()->getAutoGenerateProxyClasses()) {
            return;
        }

        $output->writeln('Generate Doctrine proxy classes for extended entities');

        /** @var EntityProxyGenerator $proxyGenerator */
        $proxyGenerator = $this->getContainer()->get('oro_entity_extend.extend.entity_proxy_generator');

        $cacheDir = $proxyGenerator->getCacheDir();
        if (empty($this->cacheDir) || $this->cacheDir === $cacheDir) {
            $proxyGenerator->generateProxies();
        } else {
            $proxyGenerator->setCacheDir($this->cacheDir);
            try {
                $proxyGenerator->generateProxies();
                $proxyGenerator->setCacheDir($cacheDir);
            } catch (\Exception $e) {
                $proxyGenerator->setCacheDir($cacheDir);
                throw $e;
            }
        }
    }

    /**
     * Sets class aliases for extended entities.
     *
     * @param string $cacheDir The cache directory
     *
     * @throws \ReflectionException
     */
    protected function setClassAliases($cacheDir)
    {
        $aliases = ExtendClassLoadingUtils::getAliases($cacheDir);
        foreach ($aliases as $className => $alias) {
            if (class_exists($className)) {
                if (class_exists($alias, false)) {
                    throw new \ReflectionException(
                        sprintf(
                            'The alias "%1$s" for the class "%2$s" cannot be registered '
                            . 'because the class "%1$s" is already loaded. '
                            . 'This may happen if this class or a derived class '
                            . 'is used in EntityConfigDumperExtension or EntityGeneratorExtension.',
                            $alias,
                            $className
                        )
                    );
                }

                class_alias($className, $alias);
            }
        }
    }
}

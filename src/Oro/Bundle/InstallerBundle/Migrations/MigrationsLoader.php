<?php

namespace Oro\Bundle\InstallerBundle\Migrations;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\InstallerBundle\Migrations\MigrationTable\CreateTableMigration;
use Oro\Bundle\InstallerBundle\Migrations\MigrationTable\UpdateBundleVersions;

class MigrationsLoader
{
    const MIGRATIONS_PATH = 'Migration';
    const MIGRATION_TABLE = 'oro_installer_migrations';

    /**
     * @var KernelInterface
     *
     */
    protected $kernel;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array An array with bundles we must work from
     */
    protected $bundles;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var array An array with excluded bundles
     */
    protected $excludeBundles;

    public function __construct(KernelInterface $kernel, EntityManager $em, ContainerInterface $container)
    {
        $this->kernel    = $kernel;
        $this->em        = $em;
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getMigrations()
    {
        $bundles          = $this->getBundleList();
        $bundleMigrations = [];
        foreach ($bundles as $bundleName => $bundle) {
            /** @var $bundle BundleInterface */
            $bundlePath          = $bundle->getPath();
            $bundleMigrationPath = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $bundlePath . '/' . self::MIGRATIONS_PATH
            );

            $finder = new Finder();

            try {
                $finder->directories()->depth(0)->in($bundleMigrationPath);
                /** @var SplFileInfo $directory */
                foreach ($finder as $directory) {
                    if (!isset($bundleMigrations[$bundleName])) {
                        $bundleMigrations[$bundleName] = [];
                    }
                    $bundleMigrations[$bundleName][$directory->getRelativePathname()] = $directory->getPathname();
                }
            } catch (\Exception $e) {
                //dir doesn't exists
            }
        }

        $bundleMigrations = $this->checkMigrationVersions($bundleMigrations);
        $fileFinder       = new Finder();
        $includedFiles    = [];
        $bundleVersions   = [];
        foreach ($bundleMigrations as $bundleName => $migrations) {
            uksort(
                $migrations,
                function ($a, $b) {
                    return version_compare($a, $b);
                }
            );

            foreach ($migrations as $migrationPath) {
                $fileFinder->files()->name('*.php')->in($migrationPath);
                foreach ($fileFinder as $file) {
                    /** @var SplFileInfo $file */
                    include_once $file->getPathname();
                    $includedFiles[] = $file->getPathname();
                }
            }
            if ($version = array_pop(array_keys($migrations))) {
                $bundleVersions[$bundleName] = $version;
            }
        }

        $migrations = $this->getMigrationFiles($includedFiles);

        if (!$this->checkMigrationTable()) {
            array_unshift($migrations, new CreateTableMigration());
        }
        if (!empty($bundleVersions)) {
            $updateVersionMigration = new UpdateBundleVersions();
            $updateVersionMigration->setBundleVersions($bundleVersions);
            $migrations[] = $updateVersionMigration;
        }

        return $migrations;
    }

    /**
     * @return array
     *   key - class name of migration file
     *   value - array of sql queries from this file
     */
    public function getMigrationsQueries()
    {
        $connection       = $this->em->getConnection();
        $sm               = $connection->getSchemaManager();
        $platform         = $connection->getDatabasePlatform();
        $migrations       = $this->getMigrations();
        $migrationQueries = [];
        foreach ($migrations as $migration) {
            $fromSchema = $sm->createSchema();
            $toSchema   = clone $fromSchema;
            $queries    = $migration->up($toSchema);
            $queries    = array_merge($queries, $fromSchema->getMigrateToSql($toSchema, $platform));

            $migrationQueries[get_class($migration)] = $queries;
        }

        return $migrationQueries;
    }

    /**
     * @param $includedFiles
     * @return array
     */
    protected function getMigrationFiles($includedFiles)
    {
        $declared   = get_declared_classes();
        $migrations = [];
        foreach ($declared as $className) {
            $reflClass  = new \ReflectionClass($className);
            $sourceFile = $reflClass->getFileName();
            if (in_array($sourceFile, $includedFiles)) {
                $migration = new $className;
                if ($migration instanceof ContainerAwareInterface) {
                    $migration->setContainer($this->container);
                }
                $migrations[] = $migration;
            }
        }

        return $migrations;
    }

    /**
     * @param $bundleMigrations
     * @return mixed
     */
    protected function checkMigrationVersions($bundleMigrations)
    {
        if ($this->checkMigrationTable()) {
            $versions = $this->em->getConnection()->fetchAll(
                sprintf(
                    'SELECT * FROM %s where id in (select max(id) from %s group by bundle)',
                    self::MIGRATION_TABLE,
                    self::MIGRATION_TABLE
                )
            );
            if (!empty($versions)) {
                foreach ($bundleMigrations as $bundleName => $migrations) {
                    $bundleVersion = array_filter(
                        $versions,
                        function ($val) use ($bundleName) {
                            return ($val['bundle'] == $bundleName);
                        }
                    );
                    $dbVersion     = array_pop($bundleVersion)['version'];
                    foreach (array_keys($migrations) as $migrationKey) {
                        if (version_compare($migrationKey, $dbVersion) < 1) {
                            unset ($bundleMigrations[$bundleName][$migrationKey]);
                        }
                    }
                }
            }
        }

        return $bundleMigrations;
    }

    /**
     * Check if user table exists in db
     *
     * @return bool
     */
    protected function checkMigrationTable()
    {
        $result = false;
        try {
            $conn = $this->em->getConnection();

            if (!$conn->isConnected()) {
                $this->em->getConnection()->connect();
            }

            $result = $conn->isConnected()
                && (bool)array_intersect(
                    [self::MIGRATION_TABLE],
                    $this->em->getConnection()->getSchemaManager()->listTableNames()
                );
        } catch (\PDOException $e) {
        }

        return $result;
    }

    /**
     * @return BundleInterface[]
     */
    protected function getBundleList()
    {
        $bundles = $this->kernel->getBundles();
        if (!empty($this->bundles)) {
            $includedBundles = [];
            foreach ($this->bundles as $bundleName) {
                if (isset($bundles[$bundleName])) {
                    $includedBundles[$bundleName] = $bundles[$bundleName];
                }
            }
            $bundles = $includedBundles;
        }
        if (!empty($this->excludeBundles)) {
            foreach ($this->excludeBundles as $excludeBundle) {
                unset($bundles[$excludeBundle]);
            }
        }

        return $bundles;
    }
}

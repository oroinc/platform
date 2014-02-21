<?php

namespace Oro\Bundle\InstallerBundle\Migrations;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\MappingException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\InstallerBundle\Migrations\MigrationTable\CreateMigrationTableMigration;
use Oro\Bundle\InstallerBundle\Migrations\MigrationTable\UpdateBundleVersionMigration;

class MigrationsLoader
{
    const MIGRATIONS_PATH       = 'Migration';
    const MAX_TABLE_NAME_LENGTH = 30;

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

    /**
     * @param KernelInterface    $kernel
     * @param EntityManager      $em
     * @param ContainerInterface $container
     */
    public function __construct(KernelInterface $kernel, EntityManager $em, ContainerInterface $container)
    {
        $this->kernel    = $kernel;
        $this->em        = $em;
        $this->container = $container;
    }

    /**
     * @param array $bundles
     */
    public function setBundles($bundles)
    {
        $this->bundles = $bundles;
    }

    /**
     * @param array $excludeBundles
     */
    public function setExcludeBundles($excludeBundles)
    {
        $this->excludeBundles = $excludeBundles;
    }

    /**
     * @return Migration[]
     */
    public function getMigrations()
    {
        $migrations = [];

        $migrationDirectories = $this->getMigrationDirectories();
        if ($this->isMigrationTableExist()) {
            $this->filterMigrations($migrationDirectories);
        } else {
            $migrations[] = new CreateMigrationTableMigration();
        }

        $includedFiles = $this->includeMigrationFiles($migrationDirectories);
        $migrations    = $this->createMigrationObjects($includedFiles);

        $bundleVersions = $this->getLatestMigrationVersions($migrationDirectories);
        if (!empty($bundleVersions)) {
            $migrations[] = new UpdateBundleVersionMigration($bundleVersions);
        }

        return $migrations;
    }

    /**
     * @return array
     *   key - class name of migration file
     *   value - array of sql queries from this file
     * @throws MappingException
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
            $comparator = new Comparator();
            $schemaDiff = $comparator->compare($fromSchema, $toSchema);
            foreach ($schemaDiff->newTables as $newTable) {
                if (strlen($newTable->getName()) > self::MAX_TABLE_NAME_LENGTH) {
                    throw new MappingException(
                        sprintf(
                            'Max table name length is %s. Please correct table %s in %s migration',
                            self::MAX_TABLE_NAME_LENGTH,
                            $newTable->getName(),
                            get_class($migration)
                        )
                    );
                }
            }
            $queries = array_merge($queries, $schemaDiff->toSql($platform));

            $migrationQueries[get_class($migration)] = $queries;
        }

        return $migrationQueries;
    }

    /**
     * Gets a list of all directories contain migration scripts
     *
     * @return array
     *      key   = bundle name
     *      value = array
     *      .    key   = a migration version (actually it equals the name of migration directory)
     *      .    value = full path to a migration directory
     */
    protected function getMigrationDirectories()
    {
        $result = [];

        $bundles = $this->getBundleList();
        foreach ($bundles as $bundleName => $bundle) {
            $bundlePath          = $bundle->getPath();
            $bundleMigrationPath = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $bundlePath . '/' . self::MIGRATIONS_PATH
            );

            $finder = new Finder();

            try {
                $bundleMigrationDirectories = [];
                $finder->directories()->depth(0)->in($bundleMigrationPath);
                /** @var SplFileInfo $directory */
                foreach ($finder as $directory) {
                    $bundleMigrationDirectories[$directory->getRelativePathname()] = $directory->getPathname();
                }
                if (!empty($bundleMigrationDirectories)) {
                    uksort(
                        $bundleMigrationDirectories,
                        function ($a, $b) {
                            return version_compare($a, $b);
                        }
                    );
                    $result[$bundleName] = $bundleMigrationDirectories;
                }
            } catch (\Exception $e) {
                //dir doesn't exists
            }
        }

        return $result;
    }

    /**
     * Finds migration files and call "include_once" for each file
     *
     * @param array $migrationDirectories
     *      key   = bundle name
     *      value = array
     *      .    key   = a migration version
     *      .    value = full path to a migration directory
     * @return string[] included files
     */
    protected function includeMigrationFiles(array $migrationDirectories)
    {
        $result  = [];

        $fileFinder = new Finder();
        foreach ($migrationDirectories as $bundleMigrationDirectories) {
            foreach ($bundleMigrationDirectories as $migrationPath) {
                $fileFinder->files()->name('*.php')->in($migrationPath);
                foreach ($fileFinder as $file) {
                    /** @var SplFileInfo $file */
                    $filePath = $file->getPathname();
                    include_once $filePath;
                    $result[] = $filePath;
                }
            }
        }

        return $result;
    }

    /**
     * Extracts latest migration version for each bundle
     *
     * @param array $migrationDirectories
     *      key   = bundle name
     *      value = array
     *      .    key   = a migration version
     *      .    value = full path to a migration directory
     * @return string[]
     *      key   = bundle name
     *      value = latest migration version
     */
    protected function getLatestMigrationVersions(array $migrationDirectories)
    {
        $result = [];
        foreach ($migrationDirectories as $bundleName => $bundleMigrationDirectories) {
            $version = array_pop(array_keys($bundleMigrationDirectories));
            if ($version) {
                $result[$bundleName] = $version;
            }
        }

        return $result;
    }

    /**
     * @param string[] $files Full paths to migration scripts
     * @return Migration[]
     */
    protected function createMigrationObjects($files)
    {
        $declared   = get_declared_classes();
        $migrations = [];
        foreach ($declared as $className) {
            $reflClass  = new \ReflectionClass($className);
            $sourceFile = $reflClass->getFileName();
            if (in_array($sourceFile, $files)) {
                $migration = new $className;
                if ($migration instanceof Migration) {
                    if ($migration instanceof ContainerAwareInterface) {
                        $migration->setContainer($this->container);
                    }
                    $migrations[] = $migration;
                }
            }
        }

        return $migrations;
    }

    /**
     * Removes already installed migrations
     *
     * @param array $migrationDirectories
     *      key   = bundle name
     *      value = array
     *      .    key   = a migration version
     *      .    value = full path to a migration directory
     */
    protected function filterMigrations(array &$migrationDirectories)
    {
        // get the last installed migrations for all bundles
        $versions = $this->em->getConnection()->fetchAll(
            sprintf(
                'select * from %s where id in (select max(id) from %s group by bundle)',
                CreateMigrationTableMigration::MIGRATION_TABLE,
                CreateMigrationTableMigration::MIGRATION_TABLE
            )
        );
        if (!empty($versions)) {
            foreach ($migrationDirectories as $bundleName => $bundleMigrationDirectories) {
                $bundleVersion = array_filter(
                    $versions,
                    function ($val) use ($bundleName) {
                        return ($val['bundle'] == $bundleName);
                    }
                );
                $dbVersion     = array_pop($bundleVersion)['version'];
                foreach (array_keys($bundleMigrationDirectories) as $migrationVersion) {
                    if (version_compare($migrationVersion, $dbVersion) < 1) {
                        unset ($migrationDirectories[$bundleName][$migrationVersion]);
                    }
                }
            }
        }
    }

    /**
     * Check if a table contains migration state exists in db
     *
     * @return bool TRUE if a table exists; otherwise, FALSE
     */
    protected function isMigrationTableExist()
    {
        $result = false;
        try {
            $conn = $this->em->getConnection();

            if (!$conn->isConnected()) {
                $this->em->getConnection()->connect();
            }

            $result = $conn->isConnected()
                && (bool)array_intersect(
                    [CreateMigrationTableMigration::MIGRATION_TABLE],
                    $this->em->getConnection()->getSchemaManager()->listTableNames()
                );
        } catch (\PDOException $e) {
        }

        return $result;
    }

    /**
     * @return BundleInterface[] key = bundle name
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

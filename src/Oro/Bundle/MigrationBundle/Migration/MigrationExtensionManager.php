<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides methods to manage migration extension.
 */
class MigrationExtensionManager
{
    private const EXTENSION_AWARE_INTERFACE_SUFFIX = 'AwareInterface';

    /** @var array {extension name} => [{extension}, {extension aware interface name}, {set extension method name}] */
    protected array $extensions = [];
    private ?Connection $connection = null;
    private ?AbstractPlatform $platform = null;
    private ?DbIdentifierNameGenerator $nameGenerator = null;
    private ?LoggerInterface $logger = null;
    private bool $isDependenciesUpToDate = false;

    /**
     * Sets a database connection.
     */
    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
        foreach ($this->extensions as $extension) {
            if ($extension[0] instanceof ConnectionAwareInterface) {
                $extension[0]->setConnection($this->connection);
            }
        }
    }

    /**
     * Sets a database platform.
     */
    public function setDatabasePlatform(AbstractPlatform $platform): void
    {
        $this->platform = $platform;
        foreach ($this->extensions as $extension) {
            if ($extension[0] instanceof DatabasePlatformAwareInterface) {
                $extension[0]->setDatabasePlatform($this->platform);
            }
        }
    }

    /**
     * Sets a database identifier name generator.
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator): void
    {
        $this->nameGenerator = $nameGenerator;
        foreach ($this->extensions as $extension) {
            if ($extension[0] instanceof NameGeneratorAwareInterface) {
                $extension[0]->setNameGenerator($this->nameGenerator);
            }
        }
    }

    /**
     * Sets a logger.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        foreach ($this->extensions as $extension) {
            if ($extension[0] instanceof LoggerAwareInterface) {
                $extension[0]->setLogger($this->logger);
            }
        }
    }

    /**
     * Registers an extension.
     */
    public function addExtension(string $name, object $extension): void
    {
        $this->configureExtension($extension);

        $extensionAwareInterfaceName = $this->getExtensionAwareInterfaceName($extension);
        $this->extensions[$name] = [
            $extension,
            $extensionAwareInterfaceName,
            $this->getSetExtensionMethodName($extensionAwareInterfaceName)
        ];

        $this->isDependenciesUpToDate = false;
    }

    /**
     * Sets extensions to the given migration.
     */
    public function applyExtensions(Migration $migration): void
    {
        $this->configureExtension($migration);
        $this->ensureExtensionDependenciesApplied();
        $this->applyExtensionDependencies($migration);
    }

    /**
     * Sets external services to the given object.
     */
    protected function configureExtension(object $obj): void
    {
        if (null !== $this->connection && $obj instanceof ConnectionAwareInterface) {
            $obj->setConnection($this->connection);
        }
        if (null !== $this->platform && $obj instanceof DatabasePlatformAwareInterface) {
            $obj->setDatabasePlatform($this->platform);
        }
        if (null !== $this->nameGenerator && $obj instanceof NameGeneratorAwareInterface) {
            $obj->setNameGenerator($this->nameGenerator);
        }
        if (null !== $this->logger && $obj instanceof LoggerAwareInterface) {
            $obj->setLogger($this->logger);
        }
    }

    /**
     * Makes sure that links on depended each other extensions set.
     */
    private function ensureExtensionDependenciesApplied(): void
    {
        if (!$this->isDependenciesUpToDate) {
            foreach ($this->extensions as $extension) {
                $this->applyExtensionDependencies($extension[0]);
            }
            $this->isDependenciesUpToDate = true;
        }
    }

    /**
     * Sets extensions to the given object.
     */
    private function applyExtensionDependencies(object $obj): void
    {
        foreach ($this->extensions as $extension) {
            if (is_a($obj, $extension[1])) {
                $setMethod = $extension[2];
                $obj->$setMethod($extension[0]);
            }
        }
    }

    /**
     * Gets an name of interface which should be used to register an extension in a migration class.
     */
    private function getExtensionAwareInterfaceName(object $extension): string
    {
        $result = null;

        $extensionClassName = \get_class($extension);
        while ($extensionClassName) {
            $extensionAwareInterfaceName = $extensionClassName . self::EXTENSION_AWARE_INTERFACE_SUFFIX;
            if (interface_exists($extensionAwareInterfaceName)) {
                $result = $extensionAwareInterfaceName;
                break;
            }

            $extensionClassName = get_parent_class($extensionClassName);
        }

        if (!$result) {
            if (get_parent_class($extension)) {
                $msg = sprintf(
                    'The extension aware interface for neither "%s" not one of its parent classes was not found.',
                    \get_class($extension)
                );
            } else {
                $msg = sprintf(
                    'The extension aware interface for "%s" was not found. Make sure that "%s" interface is declared.',
                    \get_class($extension),
                    \get_class($extension) . self::EXTENSION_AWARE_INTERFACE_SUFFIX
                );
            }

            throw new \RuntimeException($msg);
        }

        return $result;
    }

    /**
     * Gets a name of set extension method.
     */
    private function getSetExtensionMethodName(string $extensionAwareInterfaceName): string
    {
        $parts = explode('\\', $extensionAwareInterfaceName);
        $className = array_pop($parts);
        $extensionName = substr($className, 0, \strlen($className) - \strlen(self::EXTENSION_AWARE_INTERFACE_SUFFIX));
        $setMethodName = 'set' . $extensionName;

        if (!EntityPropertyInfo::methodExists($extensionAwareInterfaceName, $setMethodName)) {
            throw new \RuntimeException(sprintf(
                'The method "%s::%s" was not found.',
                $extensionAwareInterfaceName,
                $setMethodName
            ));
        }

        return $setMethodName;
    }
}

<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;

class MigrationExtensionManager
{
    const EXTENSION_AWARE_INTERFACE_SUFFIX = 'AwareInterface';

    /**
     * @var array {extension name} => [{extension}, {extension aware interface name}, {set extension method name}]
     */
    protected $extensions = [];

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;

        foreach ($this->extensions as $extension) {
            if ($extension[0] instanceof DatabasePlatformAwareInterface) {
                $extension[0]->setDatabasePlatform($this->platform);
            }
        }
    }

    /**
     * Registers an extension
     *
     * @param string $name      The extension name
     * @param object $extension The extension object
     */
    public function addExtension($name, $extension)
    {
        if ($this->platform && $extension instanceof DatabasePlatformAwareInterface) {
            $extension->setDatabasePlatform($this->platform);
        }

        $extensionAwareInterfaceName = $this->getExtensionAwareInterfaceName($extension);
        $this->extensions[$name] = [
            $extension,
            $extensionAwareInterfaceName,
            $this->getSetExtensionMethodName($extensionAwareInterfaceName)
        ];
    }

    /**
     * Sets extensions to the given migration
     *
     * @param Migration $migration
     */
    public function applyExtensions(Migration $migration)
    {
        foreach ($this->extensions as $extension) {
            if (is_a($migration, $extension[1])) {
                $setMethod = $extension[2];
                $migration->$setMethod($extension[0]);
            }
        }
    }

    /**
     * Gets an name of interface which should be used to register an extension in a migration class
     *
     * @param object $extension
     * @return string
     * @throws \RuntimeException if the interface is not found
     */
    protected function getExtensionAwareInterfaceName($extension)
    {
        $result = null;

        $extensionClassName = get_class($extension);
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
                    get_class($extension)
                );
            } else {
                $msg = sprintf(
                    'The extension aware interface for "%s" was not found. Make sure that "%s" interface is declared.',
                    get_class($extension),
                    get_class($extension) . self::EXTENSION_AWARE_INTERFACE_SUFFIX
                );
            }

            throw new \RuntimeException($msg);
        }

        return $result;
    }

    /**
     * Gets a name of set extension method
     *
     * @param string $extensionAwareInterfaceName
     * @return string
     * @throws \RuntimeException if set method is not found
     */
    protected function getSetExtensionMethodName($extensionAwareInterfaceName)
    {
        $parts = explode('\\', $extensionAwareInterfaceName);
        $className = array_pop($parts);
        $extensionName = substr($className, 0, strlen($className) - strlen(self::EXTENSION_AWARE_INTERFACE_SUFFIX));
        $setMethodName = 'set' . $extensionName;

        if (!method_exists($extensionAwareInterfaceName, $setMethodName)) {
            throw new \RuntimeException(
                sprintf('The method "%s::%s" was not found.', $extensionAwareInterfaceName, $setMethodName)
            );
        }

        return $setMethodName;
    }
}

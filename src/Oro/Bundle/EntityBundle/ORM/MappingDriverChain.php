<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain as BaseMappingDriverChain;
use Doctrine\Persistence\Mapping\MappingException;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Adds a memory cache for the result value of isTransient() method.
 */
class MappingDriverChain extends BaseMappingDriverChain
{
    /** @var bool[] */
    private $isTransientCache = [];

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        if (isset($this->isTransientCache[$className])) {
            return $this->isTransientCache[$className];
        }

        $result = parent::isTransient($className);
        $this->isTransientCache[$className] = $result;

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        foreach ($this->getDrivers() as $namespace => $driver) {
            assert($driver instanceof MappingDriver);
            if (strpos($className, $namespace) === 0) {
                $driver->loadMetadataForClass($className, $metadata);
                if ($namespace !== ExtendClassLoadingUtils::getEntityNamespace()) {
                    $this->doLoadMedataForExtendEntity($className, $metadata);
                }

                return;
            }
        }

        if ($this->getDefaultDriver() !== null) {
            $this->getDefaultDriver()->loadMetadataForClass($className, $metadata);

            return;
        }

        throw MappingException::classNotFoundInNamespaces($className, array_keys($this->getDrivers()));
    }

    private function doLoadMedataForExtendEntity($className, ClassMetadata $metadata): bool
    {
        if (!ExtendHelper::isExtendEntity($className)) {
            return false;
        }
        $extendMappingDriver = $this->getExtendEntityDriver();
        if (null !== $extendMappingDriver
            && in_array($className, $extendMappingDriver->getAllClassNames())) {
            $extendMappingDriver->loadMetadataForClass($className, $metadata);

            return true;
        }

        return false;
    }

    private function getExtendEntityDriver(): ?MappingDriver
    {
        return $this->getDrivers()[ExtendClassLoadingUtils::getEntityNamespace()] ?? null;
    }
}

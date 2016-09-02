<?php

namespace Oro\Bundle\EntityBundle\Fallback;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\FallbackProviderNotFoundException;
use Oro\Bundle\EntityBundle\Exception\InvalidFallbackKeyException;
use Oro\Bundle\EntityBundle\Fallback\Provider\EntityFallbackProviderInterface;

class EntityFallbackResolver
{
    /**
     * @var EntityFallbackProviderInterface[]
     */
    protected $fallbackProviders = [];

    /**
     * @var ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * EntityFallbackResolver constructor.
     *
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(ConfigProvider $entityConfigProvider)
    {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    public function getFallbackValue($object, $objectFieldName)
    {
        $fallbackValue = $this->accessor->getValue($object, $objectFieldName);
        // if object field is not fallback type, just return it
        if (!$fallbackValue instanceof EntityFieldFallbackValue) {
            return $fallbackValue;
        }
        if (!is_null($fallbackValue->getStringValue()) || is_null($fallbackValue->getFallback())) {
            return $fallbackValue->getStringValue();
        }

        // Read the fallback configuration for the current object
        $fallbackConfiguration = $this->entityConfigProvider
            ->getConfig(get_class($object), $objectFieldName)
            ->getValues();

        $objectFallbackKey = $fallbackValue->getFallback();
        if (!array_key_exists($objectFallbackKey, $fallbackConfiguration)) {
            throw new InvalidFallbackKeyException($objectFallbackKey);
        }

        // get the fallback entity
        $fallbackHolderEntity = $this->getFallbackProvider($objectFallbackKey)->getFallbackHolderEntity(
            $object,
            $objectFieldName,
            $fallbackValue,
            $fallbackConfiguration[$objectFallbackKey]
        );

        // If provider returns a value instead of a new entity, just return it
        if (!is_object($fallbackHolderEntity)) {
            return $fallbackHolderEntity;
        }

        // get fallback field configuration for current fallback type
        $fallbackEntityConfig = $fallbackConfiguration[$fallbackValue->getFallback()];

        return $this->getFallbackValue($fallbackHolderEntity, $fallbackEntityConfig['fieldName']);
    }

    /**
     * @param EntityFallbackProviderInterface $provider
     *
     * @return $this
     */
    public function addFallbackProvider(EntityFallbackProviderInterface $provider)
    {
        $this->fallbackProviders[$provider->getId()] = $provider;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return EntityFallbackProviderInterface
     * @throws FallbackProviderNotFoundException
     */
    public function getFallbackProvider($key)
    {
        if (!array_key_exists($key, $this->fallbackProviders)) {
            throw new FallbackProviderNotFoundException($key);
        }

        return $this->fallbackProviders[$key];
    }
}

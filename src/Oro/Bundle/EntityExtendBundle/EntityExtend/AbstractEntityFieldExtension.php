<?php

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityStaticCache;

/**
 * Base extend entity field extension
 */
abstract class AbstractEntityFieldExtension implements EntityFieldExtensionInterface
{
    protected const COLLECTION_TARGET = 'target';
    protected const COLLECTION_ADD = 'add';
    protected const COLLECTION_REMOVE = 'remove';

    protected array $extensionBoolCache = [];
    protected array $extensionCache = [];
    protected array $methodExists = [];

    protected function setBoolCacheItem(
        EntityFieldProcessTransport $transport,
        object $extension,
        bool $value,
        ?string $propertyName = null
    ): void {
        $propertyName = $propertyName ?? $transport->getName();
        $this->extensionBoolCache[$transport->getClass()][$extension::class][$propertyName] = $value;
    }

    protected function getBoolCacheItem(
        EntityFieldProcessTransport $transport,
        object $extension,
        ?string $propertyName = null
    ): ?bool {
        $propertyName = $propertyName ?? $transport->getName();
        if (!isset($this->extensionBoolCache[$transport->getClass()][$extension::class][$propertyName])) {
            return null;
        }

        return $this->extensionBoolCache[$transport->getClass()][$extension::class][$propertyName];
    }

    protected function setExtensionCacheItem(
        EntityFieldProcessTransport $transport,
        string                      $extensionClass,
        string                      $key,
        mixed                       $value
    ): void {
        $this->extensionCache[$transport->getClass()][$extensionClass][$key] = $value;
    }

    protected function getCachedExtensionItem(
        EntityFieldProcessTransport $transport,
        string                      $extensionClass,
        string                      $key
    ): mixed {
        if (!isset($this->extensionCache[$transport->getClass()][$extensionClass][$key])) {
            return null;
        }

        return $this->extensionCache[$transport->getClass()][$extensionClass][$key];
    }

    /**
     * @inheritDoc
     */
    public function isset(EntityFieldProcessTransport $transport): void
    {
    }

    /**
     * @inheritDoc
     */
    public function getMethods(EntityFieldProcessTransport $transport): array
    {
        return [];
    }

    protected function initializeDefaultValue(EntityFieldProcessTransport $transport, string $propertyName = null): void
    {
        $propertyName = $propertyName ?? $transport->getName();
        $defaultValue = null;
        if (!$transport->getStorage()->offsetExists($propertyName)) {
            if ($transport->getObjectVar($propertyName)) {
                $defaultValue = $transport->getObjectVar($propertyName);
            } elseif (array_key_exists($propertyName, $this->getCollectionFields($transport))) {
                $defaultValue = new ArrayCollection();
            } elseif (null === $defaultValue) {
                $defaultValue = $this->tryGetDefaultFromMetadata($transport, $propertyName);
            }
            $transport->getStorage()->offsetSet($propertyName, $defaultValue);
        }
    }

    private function tryGetDefaultFromMetadata(
        EntityFieldProcessTransport $transport,
        string $propertyName = null
    ): mixed {
        $defaultValue = null;
        $fieldsMetadata = $transport->getFieldsMetadata();
        if (isset($fieldsMetadata[$propertyName]['default'])) {
            return $fieldsMetadata[$propertyName]['default'];
        }

        return $defaultValue;
    }

    protected function getCollectionFields(EntityFieldProcessTransport $transport): array
    {
        $cachedValue = $this->getCachedExtensionItem(
            $transport,
            $this::class,
            'getCollectionFields'
        );
        if (null !== $cachedValue) {
            return $cachedValue;
        }
        $result = [];
        $entityValues = $transport->getEntityMetadata()->getValues();
        if (isset($entityValues['schema']['addremove']) && is_array($entityValues['schema']['addremove'])) {
            foreach ($entityValues['schema']['addremove'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['target'])) {
                    if ($fieldConfig['is_target_addremove']) {
                        $addMethod = EntityFieldAccessorsHelper::adderName($fieldConfig['target']);
                        $removeMethod = EntityFieldAccessorsHelper::removerName($fieldConfig['target']);
                        $removeSelf = true;
                    } else {
                        $addMethod = EntityFieldAccessorsHelper::setterName($fieldConfig['target']);
                        $removeMethod = EntityFieldAccessorsHelper::setterName($fieldConfig['target']);
                        $removeSelf = false;
                    }

                    $result[$fieldName] = [
                        self::COLLECTION_TARGET => true,
                        self::COLLECTION_ADD => [$addMethod, true],
                        self::COLLECTION_REMOVE => [$removeMethod, $removeSelf],
                    ];
                } else {
                    $result[$fieldName] = [
                        self::COLLECTION_TARGET => false,
                    ];
                }
            }
        }
        $this->setExtensionCacheItem($transport, $this::class, 'getCollectionFields', $result);

        return $result;
    }

    protected function removeItemFromCollection(
        Collection $collection,
        object $item,
        EntityFieldProcessTransport $transport,
        string $fieldName
    ): void {
        $collectionFields = $this->getCollectionFields($transport);
        if (!isset($collectionFields[$fieldName])) {
            return;
        }
        if (!$collection->contains($item)) {
            return;
        }

        $fieldConfig = $collectionFields[$fieldName];

        $collection->removeElement($item);
        if ($fieldConfig[static::COLLECTION_TARGET]) {
            call_user_func(
                [$item, $fieldConfig[static::COLLECTION_REMOVE][0]],
                $fieldConfig[static::COLLECTION_REMOVE][1] ? $transport->getObject() : null
            );
        }
    }

    protected function addItemToCollection(
        Collection $collection,
        object $item,
        EntityFieldProcessTransport $transport,
        string $fieldName
    ): void {
        $collectionFields = $this->getCollectionFields($transport);
        if (!isset($collectionFields[$fieldName])) {
            return;
        }
        if ($collection->contains($item)) {
            return;
        }

        $fieldConfig = $collectionFields[$fieldName];

        $collection->add($item);
        if ($fieldConfig[static::COLLECTION_TARGET]) {
            call_user_func(
                [$item, $fieldConfig[static::COLLECTION_ADD][0]],
                $fieldConfig[static::COLLECTION_ADD][1] ? $transport->getObject() : null
            );
        }
    }

    protected function setCollectionTypeField(EntityFieldProcessTransport $transport, string $propertyName): void
    {
        if (!array_key_exists($propertyName, $this->getCollectionFields($transport))) {
            return;
        }
        $value = $transport->getValue();
        $transport->setProcessed(true);

        if ($value instanceof PersistentCollection) {
            $transport->getStorage()->offsetSet($propertyName, $value);
            ExtendEntityStaticCache::allowIgnoreSetCache($transport);

            return;
        }

        if (!$transport->getStorage()->offsetExists($propertyName)) {
            $transport->getStorage()->offsetSet($propertyName, new ArrayCollection());
        }

        if (null === $transport->getValue()) {
            return;
        }

        $collection = $transport->getStorage()[$transport->getName()];
        foreach ($collection as $item) {
            $this->removeItemFromCollection($collection, $item, $transport, $transport->getName());
        }

        foreach ($transport->getValue() as $item) {
            $this->addItemToCollection($collection, $item, $transport, $transport->getName());
        }
    }
}

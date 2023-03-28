<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityStaticCache;

/**
 * Default Extended Entity Field Processor Extension
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityFieldExtension extends AbstractEntityFieldExtension implements EntityFieldExtensionInterface
{
    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function isPropertyExists(EntityFieldProcessTransport $transport): bool
    {
        $cachedValue = $this->getBoolCacheItem($transport, $this);
        if (null !== $cachedValue) {
            return $cachedValue;
        }
        if (is_object($transport->getObject()) && property_exists($transport->getObject(), $transport->getName())) {
            $this->setBoolCacheItem($transport, $this, true);
            return true;
        }
        $fieldsMetadata = $transport->getFieldsMetadata();
        if (isset($fieldsMetadata[$transport->getName()])
            && $fieldsMetadata[$transport->getName()]['is_extend']
            && !$fieldsMetadata[$transport->getName()]['is_serialized']) {
            $this->setBoolCacheItem($transport, $this, true);

            return true;
        }
        $entityMetadata = $transport->getEntityMetadata();
        $isCustom = !$entityMetadata->get('inherit') && $entityMetadata->get('schema')['type'] === 'Custom';

        if ($transport->getName() === 'id' && $isCustom) {
            $this->setBoolCacheItem($transport, $this, true);
            return true;
        }

        if (in_array($transport->getName(), $this->getDefaultFields($transport), true)) {
            $this->setBoolCacheItem($transport, $this, true);
            return true;
        }

        if (array_key_exists($transport->getName(), $this->getRelationFields($transport))) {
            $this->setBoolCacheItem($transport, $this, true);
            return true;
        }

        $this->setBoolCacheItem($transport, $this, false);

        return false;
    }

    private function getDefaultFields(EntityFieldProcessTransport $transport): array
    {
        $result = [];
        $entityValues = $transport->getEntityMetadata()->getValues();
        if (isset($entityValues['schema']['property']) && is_array($entityValues['schema']['property'])) {
            $result = array_merge($result, array_keys($entityValues['schema']['property']));
        }
        if (isset($entityValues['schema']['default']) && is_array($entityValues['schema']['default'])) {
            $result = array_merge($result, array_keys($entityValues['schema']['default']));
        }

        return $result;
    }

    private function getRelationFields(EntityFieldProcessTransport $transport): array
    {
        $result = [];
        $entityValues = $transport->getEntityMetadata()->getValues();
        foreach ($entityValues['relation'] ?? [] as $relation) {
            /** @var ConfigIdInterface $field */
            $field = $relation['field_id'];
            $result[$field->getFieldName()] = $field->getFieldType();
        }

        return $result;
    }

    private function getGetMethods(EntityFieldProcessTransport $transport): array
    {
        $cachedValue = $this->getCachedExtensionItem($transport, $this::class, 'getGetMethods');
        if (null !== $cachedValue) {
            return $cachedValue;
        }
        $result = [];
        $entityMetadata = $transport->getEntityMetadata();
        $isCustom = !$entityMetadata->get('inherit') && $entityMetadata->get('schema')['type'] === 'Custom';
        if ($isCustom) {
            $result['getId'] = 'id';
        }

        foreach ($transport->getFieldsMetadata() as $fieldConfig) {
            if (!$fieldConfig['is_extend'] || $fieldConfig['is_serialized']) {
                continue;
            }
            $method = EntityFieldAccessorsHelper::getterName($fieldConfig['fieldName']);
            $result[$method] = $fieldConfig['fieldName'];
        }
        foreach ($this->getDefaultFields($transport) as $fieldName) {
            $method = EntityFieldAccessorsHelper::getterName($fieldName);
            $result[$method] = $fieldName;
        }
        foreach ($this->getRelationFields($transport) as $fieldName => $fieldType) {
            $method = EntityFieldAccessorsHelper::getterName($fieldName);
            if (!isset($result[$method])) {
                $result[$method] = $fieldName;
            }
        }
        $this->setExtensionCacheItem($transport, $this::class, 'getGetMethods', $result);

        return $result;
    }

    private function getSetMethods(EntityFieldProcessTransport $transport): array
    {
        $cachedValue = $this->getCachedExtensionItem($transport, $this::class, 'getSetMethods');
        if (null !== $cachedValue) {
            return $cachedValue;
        }
        $result = [];

        foreach ($transport->getFieldsMetadata() as $fieldConfig) {
            if (!$fieldConfig['is_extend'] || $fieldConfig['is_serialized']) {
                continue;
            }
            $method = EntityFieldAccessorsHelper::setterName($fieldConfig['fieldName']);
            $result[$method] = $fieldConfig['fieldName'];
        }
        foreach ($this->getDefaultFields($transport) as $fieldName) {
            $method = EntityFieldAccessorsHelper::setterName($fieldName);
            $result[$method] = $fieldName;
        }
        foreach ($this->getRelationFields($transport) as $fieldName => $fieldType) {
            $method = EntityFieldAccessorsHelper::setterName($fieldName);
            if (!isset($result[$method])) {
                $result[$method] = $fieldName;
            }
        }
        $this->setExtensionCacheItem($transport, $this::class, 'getSetMethods', $result);

        return $result;
    }

    private function getAddMethods(EntityFieldProcessTransport $transport): array
    {
        $cachedMethods = $this->getCachedExtensionItem($transport, $this::class, 'getAddMethods');
        if (null !== $cachedMethods) {
            return $cachedMethods;
        }
        $result = [];

        foreach ($this->getCollectionFields($transport) as $fieldName => $fieldConfig) {
            $method = EntityFieldAccessorsHelper::adderName($fieldName);
            $result[$method] = $fieldName;
        }
        $this->setExtensionCacheItem($transport, $this::class, 'getAddMethods', $result);

        return $result;
    }

    private function getRemoveMethods(EntityFieldProcessTransport $transport): array
    {
        $cachedMethods = $this->getCachedExtensionItem($transport, $this::class, 'getRemoveMethods');
        if (null !== $cachedMethods) {
            return $cachedMethods;
        }
        $result = [];

        foreach ($this->getCollectionFields($transport) as $fieldName => $fieldConfig) {
            $method = EntityFieldAccessorsHelper::removerName($fieldName);
            $result[$method] = $fieldName;
        }
        $this->setExtensionCacheItem($transport, $this::class, 'getRemoveMethods', $result);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function get(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isPropertyExists($transport)) {
            return;
        }
        $this->initializeDefaultValue($transport);
        ExtendEntityStaticCache::allowIgnoreGetCache($transport);
        $result = $transport->getStorage()[$transport->getName()];
        $transport->setProcessed(true);
        $transport->setResult($result);
    }

    /**
     * @inheritDoc
     */
    public function set(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isPropertyExists($transport)) {
            return;
        }
        $propertyName = $transport->getName();
        $this->setCollectionTypeField($transport, $propertyName);

        if (!$transport->isProcessed() && !property_exists($transport->getObject(), $propertyName)) {
            $transport->getStorage()->offsetSet($propertyName, $transport->getValue());
            $transport->setProcessed(true);
            ExtendEntityStaticCache::allowIgnoreSetCache($transport);
        }
    }

    /**
     * @inheritDoc
     */
    public function call(EntityFieldProcessTransport $transport): void
    {
        $this->processGetCall($transport);
        $this->processSetCall($transport);
        $this->processAddCall($transport);
        $this->processRemoveCall($transport);
    }

    private function processGetCall(EntityFieldProcessTransport $transport): void
    {
        if (str_starts_with($transport->getName(), 'get')) {
            $methods = $this->getGetMethods($transport);
            if (isset($methods[$transport->getName()])) {
                $propertyName = $methods[$transport->getName()];

                $this->initializeDefaultValue($transport, $propertyName);

                $transport->setProcessed(true);
                $transport->setResult($transport->getStorage()->offsetGet($propertyName));
            }
        }
    }

    private function processSetCall(EntityFieldProcessTransport $transport): void
    {
        if (str_starts_with($transport->getName(), 'set')) {
            $methods = $this->getSetMethods($transport);
            if (isset($methods[$transport->getName()])) {
                $propertyName = $methods[$transport->getName()];

                if (array_key_exists($propertyName, $this->getCollectionFields($transport))) {
                    if (!$transport->getStorage()->offsetExists($propertyName)) {
                        $transport->getStorage()->offsetSet($propertyName, new ArrayCollection());
                    }
                    $previousValue = $transport->getStorage()->offsetGet($propertyName);
                    $value = $transport->getArgument(0);

                    if ((!$value instanceof \Traversable && !is_array($value) && !$value instanceof \ArrayAccess) ||
                        !$previousValue instanceof \Doctrine\Common\Collections\Collection) {
                        $transport->getStorage()->offsetSet($propertyName, $value);
                        $transport->setProcessed(true);
                        $transport->setResult($transport->getObject());

                        return;
                    }

                    foreach ($previousValue as $item) {
                        $this->removeItemFromCollection($previousValue, $item, $transport, $propertyName);
                    }
                    foreach ($value as $item) {
                        $this->addItemToCollection($previousValue, $item, $transport, $propertyName);
                    }
                } else {
                    $transport->getStorage()->offsetSet($propertyName, $transport->getArgument(0));
                }

                $transport->setProcessed(true);
                $transport->setResult($transport->getObject());
            }
        }
    }

    private function processAddCall(EntityFieldProcessTransport $transport): void
    {
        if (str_starts_with($transport->getName(), 'add')) {
            $methods = $this->getAddMethods($transport);
            if (isset($methods[$transport->getName()])) {
                $propertyName = $methods[$transport->getName()];

                $this->initializeDefaultValue($transport, $propertyName);
                $collection = $transport->getStorage()->offsetGet($propertyName);
                $this->addItemToCollection($collection, $transport->getArgument(0), $transport, $propertyName);

                $transport->setProcessed(true);
            }
        }
    }

    private function processRemoveCall(EntityFieldProcessTransport $transport): void
    {
        if (str_starts_with($transport->getName(), 'remove')) {
            $methods = $this->getRemoveMethods($transport);
            if (isset($methods[$transport->getName()])) {
                $propertyName = $methods[$transport->getName()];

                $this->initializeDefaultValue($transport, $propertyName);
                $collection = $transport->getStorage()[$propertyName];
                $this->removeItemFromCollection(
                    $collection,
                    $transport->getArgument(0),
                    $transport,
                    $propertyName
                );

                $transport->setProcessed(true);
            }
        }
    }


    /**
     * @inheritDoc
     */
    public function isset(EntityFieldProcessTransport $transport): void
    {
        if (is_object($transport->getObject()) &&
            property_exists($transport->getObject(), $transport->getName())) {
            $reflectionProperty = new \ReflectionProperty($transport->getObject(), $transport->getName());
            if (!$reflectionProperty->isPublic()) {
                return;
            }
        }

        if (!$this->isPropertyExists($transport)) {
            return;
        }

        $transport->setProcessed(true);
        $transport->setResult(true);
    }

    /**
     * @inheritDoc
     */
    public function propertyExists(EntityFieldProcessTransport $transport): void
    {
        if ($this->isPropertyExists($transport)) {
            $transport->setProcessed(true);
            $transport->setResult(true);
        }
    }

    /**
     * @inheritDoc
     */
    public function methodExists(EntityFieldProcessTransport $transport): void
    {
        $rules = [
            'get' => [$this, 'getGetMethods'],
            'set' => [$this, 'getSetMethods'],
            'add' => [$this, 'getAddMethods'],
            'remove' => [$this, 'getRemoveMethods'],
        ];
        $exists = false;
        foreach ($rules as $prefix => $callback) {
            if (str_starts_with($transport->getName(), $prefix)) {
                $methods = call_user_func($callback, $transport);
                $exists = $exists
                    || EntityPropertyInfo::isMethodMatchExists(array_keys($methods), $transport->getName());
                if ($exists) {
                    $transport->setResult(true);
                    $transport->setProcessed(true);
                    ExtendEntityStaticCache::setMethodExistsCache($transport, true);

                    return;
                }
            }
        }
    }

    public function getMethods(EntityFieldProcessTransport $transport): array
    {
        return array_merge(
            $this->getSetMethods($transport),
            $this->getGetMethods($transport),
            $this->getRemoveMethods($transport),
            $this->getAddMethods($transport),
        );
    }
}

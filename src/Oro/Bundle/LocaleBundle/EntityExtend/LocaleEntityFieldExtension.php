<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\EntityExtend;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityExtendBundle\EntityExtend\AbstractEntityFieldExtension;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldExtensionInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityStaticCache;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Provider\DefaultFallbackMethodsNamesProvider;
use Oro\Bundle\LocaleBundle\Storage\EntityFallbackFieldsStorage;

/**
 * Locale entity field extension.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LocaleEntityFieldExtension extends AbstractEntityFieldExtension implements EntityFieldExtensionInterface
{
    private const CLONE_METHOD = 'cloneLocalizedFallbackValueAssociations';
    private const SET_FALLBACK_METHOD = 'setDefaultFallbackValue';

    private const PROPERTY_FIELD = 'field';
    private const PROPERTY_GETTER = 'getter';
    private const PROPERTY_SETTER = 'setter';
    private const METHOD_ARGUMENT = 'argument';
    private const METHOD_CALLBACK = 'callback';

    private EntityFallbackFieldsStorage $storage;
    private DefaultFallbackMethodsNamesProvider $namesProvider;

    public function __construct(
        EntityFallbackFieldsStorage $storage,
        DefaultFallbackMethodsNamesProvider $namesProvider
    ) {
        $this->storage = $storage;
        $this->namesProvider = $namesProvider;
    }

    private function getLocalizedFields(EntityFieldProcessTransport $transport): array
    {
        $fieldMap = $this->storage->getFieldMap();
        if (isset($fieldMap[$transport->getClass()])) {
            return $fieldMap[$transport->getClass()];
        }

        return [];
    }

    private function getProperties(EntityFieldProcessTransport $transport): array
    {
        $cachedValue = $this->getCachedExtensionItem(
            $transport,
            $this::class,
            'getProperties'
        );
        if (null !== $cachedValue) {
            return $cachedValue;
        }
        $result = [];

        foreach ($this->getLocalizedFields($transport) as $singularName => $fieldName) {
            $result[$fieldName] = [
                self::PROPERTY_FIELD => $fieldName,
                self::PROPERTY_GETTER => 'getStorageTransportValue',
                self::PROPERTY_SETTER => 'setStorageTransportValue',
            ];
            $result[$singularName] = [
                self::PROPERTY_FIELD => $fieldName,
                self::PROPERTY_GETTER => 'getFallbackValue',
            ];
            $result['default' . ucfirst($singularName)] = [
                self::PROPERTY_FIELD => $fieldName,
                self::PROPERTY_GETTER => 'getDefaultFallbackValue',
                self::PROPERTY_SETTER => 'setDefaultFallbackValue',
            ];
            $result['default_' . $singularName] = [
                self::PROPERTY_FIELD => $fieldName,
                self::PROPERTY_GETTER => 'getDefaultFallbackValue',
                self::PROPERTY_SETTER => 'setDefaultFallbackValue',
            ];
        }
        $this->setExtensionCacheItem($transport, $this::class, 'getProperties', $result);

        return $result;
    }

    private function getGetMethods(EntityFieldProcessTransport $transport): array
    {
        $cachedMethods = $this->getCachedExtensionItem($transport, $this::class, 'getGetMethods');
        if (null !== $cachedMethods) {
            return $cachedMethods;
        }
        $result = [];
        foreach ($this->getLocalizedFields($transport) as $singularName => $fieldName) {
            $result[$this->namesProvider->getGetterMethodName($singularName)]        = [
                self::METHOD_ARGUMENT => $fieldName,
                self::METHOD_CALLBACK => 'getFallbackValue',
            ];
            $result[$this->namesProvider->getDefaultGetterMethodName($singularName)] = [
                self::METHOD_ARGUMENT => $fieldName,
                self::METHOD_CALLBACK => 'getDefaultFallbackValue',
            ];
        }
        $this->setExtensionCacheItem($transport, $this::class, 'getGetMethods', $result);

        return $result;
    }

    private function getDefaultMethods(EntityFieldProcessTransport $transport): array
    {
        $cachedMethods = $this->getCachedExtensionItem($transport, $this::class, 'getDefaultMethods');
        if (null !== $cachedMethods) {
            return $cachedMethods;
        }
        $result = [];
        foreach ($this->getLocalizedFields($transport) as $singularName => $fieldName) {
            $getter = lcfirst(substr($this->namesProvider->getDefaultGetterMethodName($singularName), 3));
            $result[$getter] = [
                self::METHOD_ARGUMENT => $fieldName,
                self::METHOD_CALLBACK => 'getDefaultFallbackValue',
            ];
        }
        $this->setExtensionCacheItem($transport, $this::class, 'getDefaultMethods', $result);

        return $result;
    }

    private function getSetMethods(EntityFieldProcessTransport $transport): array
    {
        $cachedMethods = $this->getCachedExtensionItem(
            $transport,
            $this::class,
            'getSetMethods'
        );
        if (null !== $cachedMethods) {
            return $cachedMethods;
        }
        $result = [];
        foreach ($this->getLocalizedFields($transport) as $singularName => $fieldName) {
            $result[$this->namesProvider->getDefaultSetterMethodName($singularName)] = [
                self::METHOD_ARGUMENT => $fieldName,
                self::METHOD_CALLBACK => 'setDefaultFallbackValue',
            ];
        }
        $this->setExtensionCacheItem($transport, $this::class, 'getSetMethods', $result);

        return $result;
    }

    public function processDefaultFallbackValue(
        Collection $values,
        mixed $value,
        string $className = LocalizedFallbackValue::class
    ): void {
        $oldValue = $this->getLocalizedFallbackValue($values);
        if ($oldValue && $values->contains($oldValue)) {
            $values->removeElement($oldValue);
        }

        /** @var AbstractLocalizedFallbackValue $newValue */
        $newValue = new $className();
        $newValue->setString($value);

        if (!$values->contains($newValue)) {
            $values->add($newValue);
        }
    }

    private function getValue(Collection $values, Localization $localization = null): mixed
    {
        $result = null;
        foreach ($values as $value) {
            $valueLocalization = $value->getLocalization();
            if ($valueLocalization === $localization
                || (
                    null !== $valueLocalization
                    && null !== $localization
                    && $localization->getId() === $valueLocalization->getId()
                )
            ) {
                if (null !== $result) {
                    // The application should not be thrown. Believe that the first fallback value is correct.
                    // All other values are ignored and write error to log.
                    $message      = <<<EOF
The value "%s" is incorrect. There must be only one fallback value for localization "%s" within the "%s" class.
EOF;
                    $localization = $localization ? $localization->getName() : Localization::DEFAULT_LOCALIZATION;
                    // error_log is a temporary solution and should be removed in scope of BAP-20337
                    /** @noinspection ForgottenDebugOutputInspection */
                    /** @noinspection PhpUsageOfSilenceOperatorInspection */
                    @error_log(sprintf($message, $value, $localization, get_class($value)) . PHP_EOL);
                    break;
                }
                $result = $value;
            }
        }

        return $result;
    }

    private function getLocalizedFallbackValue(Collection $values, Localization $localization = null)
    {
        $value = $this->getValue($values, $localization);
        if (null !== $localization) {
            if ($value) {
                $fallbackType = $value->getFallback();
            } elseif ($localization->getParentLocalization()) {
                $fallbackType = FallbackType::PARENT_LOCALIZATION;
            } else {
                $fallbackType = FallbackType::SYSTEM;
            }

            switch ($fallbackType) {
                case FallbackType::PARENT_LOCALIZATION:
                    $value = $this->getLocalizedFallbackValue($values, $localization->getParentLocalization());
                    break;
                case FallbackType::SYSTEM:
                    $value = $this->getLocalizedFallbackValue($values);
                    break;
                default:
                    return $value;
            }
        }

        if (null === $value && null !== $localization) {
            // get default value
            $value = $this->getLocalizedFallbackValue($values);
        }

        return $value;
    }

    private function getStorageValue(EntityFieldProcessTransport $transport, string $fieldName): Collection
    {
        if ($transport->getObjectVar($fieldName) instanceof Collection) {
            return $transport->getObjectVar($fieldName);
        }

        if (!$transport->getStorage()->offsetExists($fieldName)) {
            $transport->getStorage()->offsetSet($fieldName, new ArrayCollection());
        }

        return $transport->getStorage()->offsetGet($fieldName);
    }

    private function getStorageTransportValue(EntityFieldProcessTransport $transport, string $fieldName): void
    {
        $value = $this->getStorageValue($transport, $fieldName);
        $transport->setResult($value);
        $transport->setProcessed(true);
    }

    private function setStorageTransportValue(EntityFieldProcessTransport $transport, string $fieldName): void
    {
        $value = $this->getStorageValue($transport, $fieldName);
        $transport->setResult($value);
        $transport->setProcessed(true);
    }

    private function getFallbackValue(EntityFieldProcessTransport $transport, string $fieldName): void
    {
        $values = $this->getStorageValue($transport, $fieldName);
        $localization = null;
        if ($transport->getArgument(0)) {
            $localization = $transport->getArgument(0);
        }

        $value = $this->getLocalizedFallbackValue($values, $localization);
        $transport->setResult($value);
        $transport->setProcessed(true);
    }

    private function getDefaultFallbackValue(EntityFieldProcessTransport $transport, string $fieldName): void
    {
        $values = $this->getStorageValue($transport, $fieldName);

        $transport->setResult($this->getLocalizedFallbackValue($values));
        $transport->setProcessed(true);
    }

    private function setDefaultFallbackValue(EntityFieldProcessTransport $transport, string $fieldName): void
    {
        $values = $this->getStorageValue($transport, $fieldName);
        $value  = $transport->getArgument(0);

        $this->processDefaultFallbackValue($values, $value);

        $transport->setResult($transport->getObject());
        $transport->setProcessed(true);
    }

    private function cloneAssociations(EntityFieldProcessTransport $transport): void
    {
        foreach ($this->getLocalizedFields($transport) as $fieldName) {
            $collection = new ArrayCollection();
            foreach ($this->getStorageValue($transport, $fieldName) as $element) {
                $collection->add(clone $element);
            }

            $transport->addResultVar($fieldName, $collection);
        }

        $transport->setProcessed(true);
    }

    /**
     * @inheritDoc
     */
    public function get(EntityFieldProcessTransport $transport): void
    {
        $properties = $this->getProperties($transport);
        if (array_key_exists($transport->getName(), $properties)) {
            $methodName = $properties[$transport->getName()][self::PROPERTY_GETTER];
            $this->$methodName($transport, $properties[$transport->getName()][self::PROPERTY_FIELD]);
        }
    }

    /**
     * @inheritDoc
     */
    public function set(EntityFieldProcessTransport $transport): void
    {
        $properties = $this->getProperties($transport);
        if (array_key_exists($transport->getName(), $properties)
            && isset($properties[$transport->getName()][self::PROPERTY_SETTER])
        ) {
            $transport->setArguments([$transport->getValue()]);
            $methodName = $properties[$transport->getName()][self::PROPERTY_SETTER];
            $this->$methodName($transport, $properties[$transport->getName()][self::PROPERTY_FIELD]);
        }
    }

    /**
     * @inheritDoc
     */
    public function call(EntityFieldProcessTransport $transport): void
    {
        if (str_starts_with($transport->getName(), 'get')) {
            $methods = $this->getGetMethods($transport);
            if (isset($methods[$transport->getName()])) {
                $methodName = $methods[$transport->getName()][self::METHOD_CALLBACK];
                $this->$methodName($transport, $methods[$transport->getName()][self::METHOD_ARGUMENT]);
            }
        }

        if (str_starts_with($transport->getName(), 'set')) {
            $methods = $this->getSetMethods($transport);
            if (isset($methods[$transport->getName()])) {
                $methodName = $methods[$transport->getName()][self::METHOD_CALLBACK];
                $this->$methodName($transport, $methods[$transport->getName()][self::METHOD_ARGUMENT]);
            }
        }

        if (str_starts_with($transport->getName(), 'default')) {
            $methods = $this->getDefaultMethods($transport);
            if (isset($methods[$transport->getName()])) {
                $methodName = $methods[$transport->getName()][self::METHOD_CALLBACK];
                $this->$methodName($transport, $methods[$transport->getName()][self::METHOD_ARGUMENT]);
            }
        }

        if ($transport->getName() === self::CLONE_METHOD) {
            $this->cloneAssociations($transport);
        }

        if ($transport->getName() === self::SET_FALLBACK_METHOD) {
            $this->processDefaultFallbackValue(
                $transport->getArgument(0),
                $transport->getArgument(1),
                $transport->getArgument(2) ?? LocalizedFallbackValue::class
            );
            $transport->setProcessed(true);
            $transport->setResult($transport->getObject());
        }
    }

    /**
     * @inheritDoc
     */
    public function isset(EntityFieldProcessTransport $transport): void
    {
        $properties = $this->getProperties($transport);
        if (array_key_exists($transport->getName(), $properties)) {
            $transport->setResult(true);
            $transport->setProcessed(true);
        }
    }

    /**
     * @inheritDoc
     */
    public function propertyExists(EntityFieldProcessTransport $transport): void
    {
        $properties = $this->getProperties($transport);
        if (array_key_exists($transport->getName(), $properties)) {
            $transport->setResult(true);
            $transport->setProcessed(true);
        }
    }

    public function methodExists(EntityFieldProcessTransport $transport): void
    {
        $exists = false;
        if ($transport->getName() === self::CLONE_METHOD) {
            $exists = true;
        }
        if (str_starts_with($transport->getName(), 'get')) {
            $methods = $this->getGetMethods($transport);
            $exists = $exists || EntityPropertyInfo::isMethodMatchExists(array_keys($methods), $transport->getName());
        }
        if (str_starts_with($transport->getName(), 'set')) {
            $methods = $this->getSetMethods($transport);
            $exists = $exists || EntityPropertyInfo::isMethodMatchExists(array_keys($methods), $transport->getName());
        }
        if (str_starts_with($transport->getName(), 'default')) {
            $methods = $this->getDefaultMethods($transport);
            $exists = $exists || EntityPropertyInfo::isMethodMatchExists(array_keys($methods), $transport->getName());
        }

        if ($exists) {
            $transport->setResult(true);
            $transport->setProcessed(true);
            ExtendEntityStaticCache::setMethodExistsCache($transport, true);
        }
    }

    public function getMethods(EntityFieldProcessTransport $transport): array
    {
        return array_merge(
            $this->getGetMethods($transport),
            $this->getSetMethods($transport),
            $this->getDefaultMethods($transport),
        );
    }
}

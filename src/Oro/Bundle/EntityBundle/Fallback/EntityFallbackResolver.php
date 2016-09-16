<?php

namespace Oro\Bundle\EntityBundle\Fallback;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackTypeException;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackProviderNotFoundException;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackKeyException;
use Oro\Bundle\EntityBundle\Fallback\Provider\EntityFallbackProviderInterface;

class EntityFallbackResolver
{
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_ARRAY = 'array';

    /** @var array */
    public static $allowedTypes = [self::TYPE_BOOLEAN, self::TYPE_STRING, self::TYPE_INTEGER, self::TYPE_ARRAY];

    /**
     * @var EntityFallbackProviderInterface[]
     */
    protected $fallbackProviders = [];

    /**
     * @var ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * @var SystemConfigurationFormProvider
     */
    protected $sysConfigFormProvider;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ConfigBag
     */
    protected $configBag;

    /**
     * EntityFallbackResolver constructor.
     *
     * @param ConfigProvider $entityConfigProvider
     * @param SystemConfigurationFormProvider $formProvider
     * @param ConfigManager $configManager
     * @param ConfigBag $configBag
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        SystemConfigurationFormProvider $formProvider,
        ConfigManager $configManager,
        ConfigBag $configBag
    ) {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->sysConfigFormProvider = $formProvider;
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->configManager = $configManager;
        $this->configBag = $configBag;
    }

    /**
     * @param object $object
     * @param string $objectFieldName
     * @return string
     * @throws FallbackFieldConfigurationMissingException
     * @throws InvalidFallbackTypeException
     */
    public function getType($object, $objectFieldName)
    {
        // get type from system configuration form description
        $formDescription = $this->getSystemConfigFormDescription($object, $objectFieldName);
        if (isset($formDescription['data_type'])) {
            return $formDescription['data_type'];
        }

        // try to read it from object field name configuration
        $fallbackType = $this->getFallbackConfig($object, $objectFieldName, EntityFieldFallbackValue::FALLBACK_TYPE);

        if (!in_array($fallbackType, static::$allowedTypes)) {
            throw new InvalidFallbackTypeException(
                sprintf(
                    "Invalid fallback data type '%s' provided. Allowed types: '%s'",
                    $fallbackType,
                    implode(',', static::$allowedTypes)
                )
            );
        }

        return $fallbackType;
    }

    /**
     * @param object $object
     * @param string $objectFieldName
     * @return array
     */
    public function getSystemConfigFormDescription($object, $objectFieldName)
    {
        $fallbackList = $this->getFallbackConfig(
            $object,
            $objectFieldName,
            EntityFieldFallbackValue::FALLBACK_LIST_KEY
        );

        if (!$configName = $this->getSystemFallbackConfigName($fallbackList)) {
            return [];
        }

        if (empty($formDescription = $this->configBag->getFieldsRoot($configName))) {
            return [];
        }

        return $formDescription;
    }

    /**
     * @param object $object
     * @param string $objectFieldName
     * @param string $fallbackId
     * @return bool
     */
    public function isFallbackSupported($object, $objectFieldName, $fallbackId)
    {
        return $this->getFallbackProvider($fallbackId)->isFallbackSupported($object, $objectFieldName);
    }

    /**
     * @param object $object
     * @param string $objectFieldName
     * @return mixed
     * @throws FallbackFieldConfigurationMissingException
     * @throws InvalidFallbackKeyException
     */
    public function getFallbackValue($object, $objectFieldName)
    {
        $fallbackValue = $this->accessor->getValue($object, $objectFieldName);
        // if object field is not fallback type, just return it
        if (!$fallbackValue instanceof EntityFieldFallbackValue) {
            return $fallbackValue;
        }
        // if fallback id is not provided and object has an own value, return it
        if (is_null($fallbackValue->getFallback()) && !is_null($fallbackValue->getOwnValue())) {
            return $this->resolveValueByType($fallbackValue->getOwnValue(), $object, $objectFieldName);
        }

        // Read the fallback configuration for the current object
        $fallbackConfiguration = $this->getFallbackConfig(
            $object,
            $objectFieldName,
            EntityFieldFallbackValue::FALLBACK_LIST_KEY
        );

        $objectFallbackKey = $fallbackValue->getFallback();

        if (!$objectFallbackKey) {
            return null;
        }

        if (!array_key_exists($objectFallbackKey, $fallbackConfiguration)) {
            throw new InvalidFallbackKeyException($objectFallbackKey);
        }

        // get the actual entity from which we need the fallback value for $object->$objectFieldName
        $fallbackHolderEntity = $this->getFallbackProvider($objectFallbackKey)
            ->getFallbackHolderEntity($object, $objectFieldName);

        // if provider returns a value instead of a new entity, just return it
        if (!is_object($fallbackHolderEntity)) {
            return $this->resolveValueByType($fallbackHolderEntity, $object, $objectFieldName);
        }

        // get fallback field configuration for current fallback type
        $fallbackEntityConfig = $fallbackConfiguration[$fallbackValue->getFallback()];

        if (!is_array($fallbackEntityConfig) || !array_key_exists('fieldName', $fallbackEntityConfig)) {
            throw new FallbackFieldConfigurationMissingException(
                sprintf(
                    "You must specify the '%s' option for the fallback '%s'",
                    'fieldName',
                    $fallbackValue->getFallback()
                )
            );
        }

        return $this->getFallbackValue($fallbackHolderEntity, $fallbackEntityConfig['fieldName']);
    }

    /**
     * @param object $object
     * @param string $objectFieldName
     * @param string|null $configName
     * @return array
     * @throws FallbackFieldConfigurationMissingException
     */
    public function getFallbackConfig($object, $objectFieldName, $configName = null)
    {
        $config = $this->entityConfigProvider
            ->getConfig(get_class($object), $objectFieldName)
            ->getValues();

        if (!$configName) {
            return $config;
        }

        if (!array_key_exists($configName, $config)) {
            throw new FallbackFieldConfigurationMissingException(
                sprintf(
                    "You must define the fallback configuration '%s' for class '%s', field '%s'",
                    $configName,
                    get_class($object),
                    $objectFieldName
                )
            );
        }

        return $config[$configName];
    }

    /**
     * @param EntityFallbackProviderInterface $provider
     * @param string $providerId
     * @return $this
     */
    public function addFallbackProvider(EntityFallbackProviderInterface $provider, $providerId)
    {
        $this->fallbackProviders[$providerId] = $provider;

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

    /**
     * @param $value
     * @param object $object
     * @param string $objectFieldName
     * @return mixed
     */
    protected function resolveValueByType($value, $object, $objectFieldName)
    {
        try {
            $type = $this->getType($object, $objectFieldName);
        } catch (\Exception $e) {
            return $value;
        }

        switch ($type) {
            case static::TYPE_BOOLEAN:
                return (bool)$value;
            case static::TYPE_STRING:
                return (string)$value;
            case static::TYPE_INTEGER:
                return (int)$value;
            case static::TYPE_ARRAY:
                return $value;
        }

        return $value;
    }

    /**
     * @param array $fallbackConfig
     * @return string|null
     */
    protected function getSystemFallbackConfigName($fallbackConfig)
    {
        if (!array_key_exists(SystemConfigFallbackProvider::FALLBACK_ID, $fallbackConfig)) {
            return null;
        }

        $systemConfig = $fallbackConfig[SystemConfigFallbackProvider::FALLBACK_ID];
        if (!array_key_exists(SystemConfigFallbackProvider::CONFIG_NAME_KEY, $systemConfig)) {
            return null;
        }

        return $systemConfig[SystemConfigFallbackProvider::CONFIG_NAME_KEY];
    }
}

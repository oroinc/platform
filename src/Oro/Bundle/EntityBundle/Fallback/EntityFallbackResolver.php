<?php

namespace Oro\Bundle\EntityBundle\Fallback;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackProviderNotFoundException;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackKeyException;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackTypeException;
use Oro\Bundle\EntityBundle\Fallback\Provider\EntityFallbackProviderInterface;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityFallbackResolver
{
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_ARRAY = 'array';

    const FALLBACK_FIELD_NAME = 'fieldName';

    /** @var array */
    public static $allowedTypes = [
        self::TYPE_BOOLEAN,
        self::TYPE_STRING,
        self::TYPE_INTEGER,
        self::TYPE_DECIMAL,
        self::TYPE_ARRAY,
    ];

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
     * @var PropertyAccessor
     */
    protected $accessor;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * EntityFallbackResolver constructor.
     *
     * @param ConfigProvider $entityConfigProvider
     * @param SystemConfigurationFormProvider $formProvider
     * @param ConfigManager $configManager
     * @param ConfigBag $configBag
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        SystemConfigurationFormProvider $formProvider,
        ConfigManager $configManager,
        ConfigBag $configBag,
        DoctrineHelper $doctrineHelper
    ) {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->sysConfigFormProvider = $formProvider;
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->configManager = $configManager;
        $this->configBag = $configBag;
        $this->doctrineHelper = $doctrineHelper;
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

        // try to read it from object's field name @ConfigField configuration
        $fallbackType = $this->getFallbackConfig($object, $objectFieldName, EntityFieldFallbackValue::FALLBACK_TYPE);

        if (!in_array($fallbackType, static::$allowedTypes)) {
            throw new InvalidFallbackTypeException($fallbackType);
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
            EntityFieldFallbackValue::FALLBACK_LIST
        );

        $configName = $this->getSystemFallbackConfigName($fallbackList);
        if (!$configName) {
            return [];
        }

        $formDescription = $this->configBag->getFieldsRoot($configName);
        if (empty($formDescription)) {
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
     * @param int $level
     * @return mixed
     * @throws InvalidFallbackKeyException
     */
    public function getFallbackValue($object, $objectFieldName, $level = 1)
    {
        $fallbackValue = $this->accessor->getValue($object, $objectFieldName);

        // Read the fallback configuration for the current object
        $fallbackList = $this->getFallbackConfig(
            $object,
            $objectFieldName,
            EntityFieldFallbackValue::FALLBACK_LIST
        );

        // if object field is not fallback type, try to get values from each provider, in the order of the
        // fallback list definition
        if (!$fallbackValue instanceof EntityFieldFallbackValue) {
            // if we have a value, try to resolve it
            if (!is_null($fallbackValue)) {
                return $this->resolveValueByType($fallbackValue, $object, $objectFieldName);
            }

            // if we are on the main origin object, proceed with searching through the default fallback list
            if (1 === $level) {
                return $this->getFallbackValueByFallbackList($fallbackList, $object, $objectFieldName, $level);
            }

            return null;
        }

        // if fallback id is not provided and object has an own value, return it
        $objectFallbackKey = $fallbackValue->getFallback();
        if (is_null($objectFallbackKey)) {
            if (!is_null($fallbackValue->getOwnValue())) {
                return $this->resolveValueByType($fallbackValue->getOwnValue(), $object, $objectFieldName);
            }

            // if both fallback and own values are null, proceed with searching through the default fallback list
            return $this->getFallbackValueByFallbackList($fallbackList, $object, $objectFieldName, $level);
        }

        if (!array_key_exists($objectFallbackKey, $fallbackList)) {
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
        $fallbackEntityConfig = $fallbackList[$fallbackValue->getFallback()];

        $this->validateFallbackPropertyExists(
            $fallbackEntityConfig,
            $fallbackValue->getFallback(),
            self::FALLBACK_FIELD_NAME
        );

        return $this->getFallbackValue(
            $fallbackHolderEntity,
            $fallbackEntityConfig[self::FALLBACK_FIELD_NAME],
            $level
        );
    }

    /**
     * @param object $object
     * @param string $objectFieldName
     * @param string $value
     * @return bool
     */
    public function hasAtLeastOneFallbackScalarValue($object, $objectFieldName, $value)
    {
        $className = get_class($object);

        $repo = $this->doctrineHelper->getEntityRepositoryForClass($className);
        if ($this->isRepositoryContainsEntityWithValue($repo, $objectFieldName, $value)) {
            return true;
        }

        $fallbackList = $this->getFallbackConfig($className, $objectFieldName, EntityFieldFallbackValue::FALLBACK_LIST);
        foreach ($fallbackList as $fallbackId => $config) {
            $provider = $this->getFallbackProvider($fallbackId);
            if ($provider instanceof SystemConfigFallbackProvider) {
                // here must be non-strict comparison, because, for instance,
                // bool values is represented as '1' or 'true' in different cases
                return $provider->getFallbackHolderEntity($object, $objectFieldName) == $value;
            }
            $fallbackClass = $provider->getFallbackEntityClass();
            if ($fallbackClass) {
                $repo = $this->doctrineHelper->getEntityRepositoryForClass($fallbackClass);
                if ($this->isRepositoryContainsEntityWithValue($repo, $objectFieldName, $value)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param object|string $object
     * @param string $objectFieldName
     * @param string|null $configName
     * @return array|string
     * @throws FallbackFieldConfigurationMissingException
     */
    public function getFallbackConfig($object, $objectFieldName, $configName = null)
    {
        $className = is_string($object) ? $object : get_class($object);

        $config = $this->entityConfigProvider
            ->getConfig($className, $objectFieldName)
            ->getValues();

        if (!$configName) {
            return $config;
        }

        if (!is_array($config) || !array_key_exists($configName, $config)) {
            throw new FallbackFieldConfigurationMissingException(
                sprintf(
                    "You must define the fallback configuration '%s' for class '%s', field '%s'",
                    $configName,
                    $className,
                    $objectFieldName
                )
            );
        }

        return $config[$configName];
    }

    /**
     * @param string $fallbackId
     * @return string
     */
    public function getFallbackLabel($fallbackId)
    {
        return $this->getFallbackProvider($fallbackId)->getFallbackLabel();
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
     * @param string $fieldType
     * @return string
     * @throws InvalidFallbackTypeException
     */
    public function getRequiredFallbackFieldByType($fieldType)
    {
        switch ($fieldType) {
            case EntityFallbackResolver::TYPE_BOOLEAN:
            case EntityFallbackResolver::TYPE_INTEGER:
            case EntityFallbackResolver::TYPE_DECIMAL:
            case EntityFallbackResolver::TYPE_STRING:
                return EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD;
            case EntityFallbackResolver::TYPE_ARRAY:
                return EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD;
            default:
                throw new InvalidFallbackTypeException($fieldType);
        }
    }

    /**
     * @param array $fallbackList
     * @param object $object
     * @param string $objectFieldName
     * @param int $level
     * @return mixed|null
     */
    protected function getFallbackValueByFallbackList($fallbackList, $object, $objectFieldName, $level)
    {
        foreach ($fallbackList as $fallbackId => $fallbackConfig) {
            // get the actual entity from which we need the fallback value for $object->$objectFieldName
            $fallbackHolderEntity = $this->getFallbackProvider($fallbackId)
                ->getFallbackHolderEntity($object, $objectFieldName);

            if (is_null($fallbackHolderEntity)) {
                continue;
            }
            // fallback holder is already an actual value, like in the case of systemConfig fallback type
            if (!is_object($fallbackHolderEntity)) {
                return $fallbackHolderEntity;
            }

            $this->validateFallbackPropertyExists($fallbackConfig, $fallbackId, self::FALLBACK_FIELD_NAME);

            // read fallback value and return it if found one, else get it from the next fallback provider
            $fallbackValue = $this->getFallbackValue(
                $fallbackHolderEntity,
                $fallbackConfig[self::FALLBACK_FIELD_NAME],
                $level + 1
            );
            if (!is_null($fallbackValue)) {
                return $fallbackValue;
            }
        }

        return null;
    }

    /**
     * @param array $fallbackConfig
     * @param string $fallbackId
     * @param string $property
     * @throws FallbackFieldConfigurationMissingException
     */
    protected function validateFallbackPropertyExists($fallbackConfig, $fallbackId, $property)
    {
        if (!is_array($fallbackConfig) || !array_key_exists($property, $fallbackConfig)) {
            throw new FallbackFieldConfigurationMissingException(
                sprintf(
                    "You must specify the '%s' option for the fallback '%s'",
                    $property,
                    $fallbackId
                )
            );
        }
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
            case static::TYPE_DECIMAL:
                return (float)$value;
            case static::TYPE_ARRAY:
                return (array)$value;
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

    /**
     * @param EntityRepository $repo
     * @param string $objectFieldName
     * @param string $value
     * @return bool
     */
    protected function isRepositoryContainsEntityWithValue(EntityRepository $repo, $objectFieldName, $value)
    {
        $qb = $repo->createQueryBuilder('e');
        $qb->select('1')
            ->innerJoin(QueryBuilderUtil::getField('e', $objectFieldName), 'fallbackValue')
            ->where($qb->expr()->eq('fallbackValue.scalarValue', ':value'))
            ->setParameter('value', $value, Type::STRING)
            ->setMaxResults(1);

        return (bool)$qb->getQuery()->getResult();
    }
}

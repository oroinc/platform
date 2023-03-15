<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Handler\TypeCast\TypeCastingHandlerRegistry;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Mapping index data from entities' data - common code.
 */
abstract class AbstractMapper
{
    protected array $mappingErrors = [];

    public function __construct(
        protected SearchMappingProvider $mappingProvider,
        protected PropertyAccessorInterface $propertyAccessor,
        protected TypeCastingHandlerRegistry $handlerRegistry,
        protected EntityNameResolver $nameResolver,
        protected DoctrineHelper $doctrineHelper
    ) {
    }

    /**
     * Get object field value
     *
     * @param object|array $objectOrArray
     * @param string       $fieldName
     *
     * @return mixed
     */
    public function getFieldValue($objectOrArray, $fieldName)
    {
        if (is_object($objectOrArray)) {
            $getter = sprintf('get%s', $fieldName);
            if (EntityPropertyInfo::methodExists($objectOrArray, $getter)) {
                $getter = EntityPropertyInfo::getMatchedMethod($objectOrArray::class, $getter);
                return $objectOrArray->$getter();
            }
        }

        try {
            return $this->propertyAccessor->getValue($objectOrArray, $fieldName);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get mapping parameter for entity
     *
     * @param string $entity
     * @param string $parameter
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getEntityMapParameter($entity, $parameter, $defaultValue = false)
    {
        return $this->mappingProvider->getEntityMapParameter($entity, $parameter, $defaultValue);
    }

    /**
     * Get mapping config for entity
     *
     * @param string $entity
     *
     * @return array
     */
    public function getEntityConfig($entity)
    {
        return $this->mappingProvider->getEntityConfig($entity);
    }

    /**
     * Returns mode attribute from entity mapping config
     *
     * @param string $entity
     *
     * @return bool|string
     */
    public function getEntityModeConfig($entity)
    {
        $value = Mode::NORMAL;

        $config = $this->getEntityConfig($entity);
        if ($config) {
            $value = $config['mode'];
        }

        return $value;
    }

    /**
     * Set value for meta fields by type
     *
     * @param string $alias
     * @param array  $objectData
     * @param array  $fieldConfig
     * @param mixed  $value
     * @param bool   $isArray
     *
     * @return array
     */
    protected function setDataValue($alias, $objectData, $fieldConfig, $value, $isArray = false)
    {
        if (null === $value || '' === $value) {
            return $objectData;
        }

        $targetFields = $fieldConfig['target_fields'] ?? [$fieldConfig['name']];
        foreach ($targetFields as $targetField) {
            try {
                $value = $this->handlerRegistry->get($fieldConfig['target_type'])->castValue($value);
            } catch (TypeCastingException $exception) {
                $this->addMappingError($alias, $targetField, $exception->getMessage());
                continue;
            }

            if ($fieldConfig['target_type'] !== Query::TYPE_TEXT) {
                if ($isArray) {
                    $objectData[$fieldConfig['target_type']][$targetField][] = $value;
                } else {
                    $objectData[$fieldConfig['target_type']][$targetField] = $value;
                }
            } else {
                if (!isset($objectData[$fieldConfig['target_type']][$targetField])) {
                    $objectData[$fieldConfig['target_type']][$targetField] = '';
                }
                $objectData[$fieldConfig['target_type']][$targetField] .= sprintf(' %s ', $value);
                $objectData[$fieldConfig['target_type']] = array_map('trim', $objectData[$fieldConfig['target_type']]);
            }
        }

        return $objectData;
    }

    /**
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return string
     */
    abstract protected function clearTextValue($fieldName, $value);

    /**
     * @param string $original
     * @param string $addition
     *
     * @return string
     */
    public function buildAllDataField($original, $addition)
    {
        $addition = $this->clearTextValue(Indexer::TEXT_ALL_DATA_FIELD, $addition);
        $clearedAddition = Query::clearString($addition);

        $original .= sprintf(' %s %s ', $addition, $clearedAddition);
        $original = implode(
            Query::DELIMITER,
            array_unique(explode(Query::DELIMITER, $original))
        );

        return trim($original);
    }

    /**
     * Fills "all_text" virtual field with data.
     */
    protected function generateAllTextField(array $objectData): array
    {
        if (empty($objectData[Query::TYPE_TEXT])) {
            return $objectData;
        }

        // "all_text" field might already exist if added by 'oro_search.prepare_entity_map' listeners.
        $textAllDataField = $objectData[Query::TYPE_TEXT][Indexer::TEXT_ALL_DATA_FIELD] ?? '';

        foreach ($objectData[Query::TYPE_TEXT] as $fieldName => $value) {
            if ($fieldName === Indexer::TEXT_ALL_DATA_FIELD) {
                continue;
            }

            $textAllDataField = $this->buildAllDataField($textAllDataField, $value);
        }

        $objectData[Query::TYPE_TEXT][Indexer::TEXT_ALL_DATA_FIELD] = $textAllDataField;

        return $objectData;
    }

    protected function getEntityId(object $entity): int
    {
        return (int)$this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    protected function getEntityName(object $entity): string
    {
        return $this->nameResolver->getName($entity, EntityNameProviderInterface::FULL) ?? '';
    }

    private function addMappingError(string $alias, string $targetField, string $message): void
    {
        if (!array_key_exists($alias, $this->mappingErrors)) {
            $this->mappingErrors[$alias] = [];
        }
        $this->mappingErrors[$alias][$targetField] = $message;
    }

    public function getLastMappingErrors(): array
    {
        $errors = $this->mappingErrors;
        $this->mappingErrors = [];

        return $errors;
    }
}

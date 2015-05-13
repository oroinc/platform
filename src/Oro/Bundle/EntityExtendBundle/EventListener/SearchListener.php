<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SearchListener
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }
    /**
     * @param PrepareEntityMapEvent $event
     */
    public function prepareEntityMapEvent(PrepareEntityMapEvent $event)
    {
        $data = $event->getData();
        $object = $event->getEntity();
        $className = $event->getClassName();
        $searchConfigs = $this->configManager->getConfigs('search', $className);
        foreach ($searchConfigs as $searchConfig) {
            if ($searchConfig->is('searchable', true)) {
                /** @var FieldConfigId $fieldId */
                $fieldId   = $searchConfig->getId();
                if (!$fieldId instanceof FieldConfigId) {
                    continue;
                }
                $field = [
                    'name' => $fieldId->getFieldName(),
                    'target_type' => $this->transformCustomType($fieldId->getFieldType()),
                    'relation_type' => 'none'
                ];

                $value = $this->getFieldValue($object, $field['name']);
                if (null === $value) {
                    continue;
                }
                switch ($field['relation_type']) {
                    case Indexer::RELATION_ONE_TO_ONE:
                    case Indexer::RELATION_MANY_TO_ONE:
                        $data = $this->setRelatedFields(
                            $data,
                            $field['relation_fields'],
                            $value,
                            $field['name']
                        );
                        break;
                    case Indexer::RELATION_MANY_TO_MANY:
                    case Indexer::RELATION_ONE_TO_MANY:
                        foreach ($value as $relationObject) {
                            $data = $this->setRelatedFields(
                                $data,
                                $field['relation_fields'],
                                $relationObject,
                                $field['name'],
                                true
                            );
                        }
                        break;
                    default:
                        $data = $this->setDataValue($data, $field, $value);
                }
            }
        }
        $event->setData($data);
    }

    public function getFieldValue($objectOrArray, $fieldName)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        try {
            return $propertyAccessor->getValue($objectOrArray, $fieldName);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set related fields values
     *
     * @param array  $objectData
     * @param array  $relationFields
     * @param object $relationObject
     * @param string $parentName
     * @param bool   $isArray
     *
     * @return array
     */
    protected function setRelatedFields(
        $objectData,
        $relationFields,
        $relationObject,
        $parentName,
        $isArray = false
    ) {
        return;
        foreach ($relationFields as $relationObjectField) {
            $value = $this->getFieldValue($relationObject, $relationObjectField['name']);
            if ($value) {
                $relationObjectField['name'] = $parentName;
                $objectData = $this->setDataValue(
                    $objectData,
                    $relationObjectField,
                    $value,
                    $isArray
                );
            }
        }

        return $objectData;
    }

    /**
     * Set value for meta fields by type
     *
     * @param array  $objectData
     * @param array  $fieldConfig
     * @param mixed  $value
     * @param bool   $isArray
     *
     * @return array
     */
    protected function setDataValue($objectData, $fieldConfig, $value, $isArray = false)
    {
        if ($value) {
            //check if field have target_fields parameter
            $targetFields = isset($fieldConfig['target_fields'])
                ? $fieldConfig['target_fields']
                : [$fieldConfig['name']];

            if ($fieldConfig['target_type'] !== Query::TYPE_TEXT) {
                foreach ($targetFields as $targetField) {
                    if ($isArray) {
                        $objectData[$fieldConfig['target_type']][$targetField][] = $value;
                    } else {
                        $objectData[$fieldConfig['target_type']][$targetField] = $value;
                    }

                }
            } else {
                foreach ($targetFields as $targetField) {
                    if (!isset($objectData[$fieldConfig['target_type']][$targetField])) {
                        $objectData[$fieldConfig['target_type']][$targetField] = '';
                    }

                    $objectData[$fieldConfig['target_type']][$targetField] .= sprintf(' %s ', $value);
                }

                $textAllDataField = '';
                if (isset($objectData[$fieldConfig['target_type']][Indexer::TEXT_ALL_DATA_FIELD])) {
                    $textAllDataField = $objectData[$fieldConfig['target_type']][Indexer::TEXT_ALL_DATA_FIELD];
                }

                $clearedValue = Query::clearString($value);
                $textAllDataField .= sprintf(' %s %s ', $value, $clearedValue);

                $objectData[$fieldConfig['target_type']][Indexer::TEXT_ALL_DATA_FIELD] = implode(
                    Query::DELIMITER,
                    array_unique(
                        explode(
                            Query::DELIMITER,
                            $textAllDataField
                        )
                    )
                );

                $objectData[$fieldConfig['target_type']] = array_map('trim', $objectData[$fieldConfig['target_type']]);
            }
        }

        return $objectData;
    }

    private function transformCustomType($type)
    {
        $customTypesMap = [
            'string' => 'text',
            'text' => 'text',
            'money' => 'decimal',
            'percent'  => 'decimal',
            'enum'  => 'text',
            'multiEnum'  => 'text',
            'bigint'  => 'text',
            'integer'  => 'integer',
            'smallint' => 'integer',
            'datetime' => 'datetime',
            'date' => 'datetime',
            'float' => 'decimal',
            'decimal' => 'decimal',
            RelationType::ONE_TO_MANY => Indexer::RELATION_ONE_TO_MANY,
            RelationType::MANY_TO_ONE => Indexer::RELATION_MANY_TO_ONE,
            RelationType::MANY_TO_MANY => Indexer::RELATION_MANY_TO_MANY,
        ];
        return isset($customTypesMap[$type]) ? $customTypesMap[$type] : $type;
    }
}

<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides virtual fields for enum and multiEnum types.
 */
class EnumVirtualFieldProvider implements VirtualFieldProviderInterface
{
    /** @var ConfigManager */
    private $configManager;

    /** @var array */
    private $virtualFields = [];

    /** @var array */
    private $virtualFieldQueries = [];

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFields($className)
    {
        $this->ensureVirtualFieldsInitialized($className);

        return isset($this->virtualFields[$className])
            ? array_keys($this->virtualFields[$className])
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualField($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized($className);

        return
            isset($this->virtualFields[$className])
            && array_key_exists($fieldName, $this->virtualFields[$className]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFieldQuery($className, $fieldName)
    {
        $this->ensureVirtualFieldQueriesInitialized($className);

        return $this->virtualFieldQueries[$className][$fieldName]['query'];
    }

    /**
     * @param string $className
     */
    private function ensureVirtualFieldsInitialized($className)
    {
        if (isset($this->virtualFields[$className])) {
            return;
        }

        $this->virtualFields[$className] = $this->loadVirtualFields($className);
    }

    /**
     * @param string $className
     */
    private function ensureVirtualFieldQueriesInitialized($className)
    {
        if (isset($this->virtualFieldQueries[$className])) {
            return;
        }

        $this->ensureVirtualFieldsInitialized($className);
        if (isset($this->virtualFields[$className])) {
            $this->virtualFieldQueries[$className] = $this->loadVirtualFieldQueries($this->virtualFields[$className]);
        }
    }

    /**
     * @param string $className
     *
     * @return array [associationName => targetFieldName for enum and NULL for multiEnum, ...]
     */
    private function loadVirtualFields($className)
    {
        $result = [];
        /** @var FieldConfigId[] $fieldIds */
        $fieldIds = $this->configManager->getIds('extend', $className);
        foreach ($fieldIds as $fieldId) {
            $fieldType = $fieldId->getFieldType();
            if ('enum' !== $fieldType && 'multiEnum' !== $fieldType) {
                continue;
            }
            $associationName = $fieldId->getFieldName();
            $fieldConfig = $this->configManager->getFieldConfig('extend', $className, $associationName);
            if (!ExtendHelper::isFieldAccessible($fieldConfig)) {
                continue;
            }
            $targetFieldName = null;
            if ('enum' === $fieldType) {
                $result[$associationName] = $fieldConfig->get('target_field');
            } elseif ('multiEnum' === $fieldType) {
                $result[$associationName] = null;
            }
        }

        return $result;
    }

    /**
     * @param array $virtualFields [associationName => targetFieldName for enum and NULL for multiEnum, ...]
     *
     * @return array [associationName => query, ...]
     */
    private function loadVirtualFieldQueries(array $virtualFields)
    {
        $result = [];
        foreach ($virtualFields as $associationName => $targetFieldName) {
            if ($targetFieldName) {
                $result[$associationName] = [
                    'query' => [
                        'select' => [
                            'expr'         => sprintf('target.%s', $targetFieldName),
                            'return_type'  => 'enum',
                            'filter_by_id' => true
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'  => sprintf('entity.%s', $associationName),
                                    'alias' => 'target'
                                ]
                            ]
                        ]
                    ]
                ];
            } else {
                $result[$associationName] = [
                    'query' => [
                        'select' => [
                            'expr'         => sprintf(
                                'entity.%s',
                                ExtendHelper::getMultiEnumSnapshotFieldName($associationName)
                            ),
                            'return_type'  => 'multiEnum',
                            'filter_by_id' => true
                        ]
                    ]
                ];
            }
        }

        return $result;
    }
}

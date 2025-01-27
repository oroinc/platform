<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides virtual fields for enum and multiEnum types.
 */
class EnumVirtualFieldProvider implements VirtualFieldProviderInterface
{
    private array $virtualFields = [];
    private array $virtualFieldQueries = [];

    public function __construct(
        private ConfigManager $configManager
    ) {
    }

    #[\Override]
    public function getVirtualFields($className): array
    {
        $this->ensureVirtualFieldsInitialized($className);

        return isset($this->virtualFields[$className])
            ? array_keys($this->virtualFields[$className])
            : [];
    }

    #[\Override]
    public function isVirtualField($className, $fieldName): bool
    {
        $this->ensureVirtualFieldsInitialized($className);

        return isset($this->virtualFields[$className])
            && array_key_exists($fieldName, $this->virtualFields[$className]);
    }

    #[\Override]
    public function getVirtualFieldQuery($className, $fieldName)
    {
        $this->ensureVirtualFieldQueriesInitialized($className);

        return $this->virtualFieldQueries[$className][$fieldName]['query'];
    }

    /**
     * @param string $className
     */
    private function ensureVirtualFieldsInitialized($className): void
    {
        if (isset($this->virtualFields[$className])) {
            return;
        }

        $this->virtualFields[$className] = $this->loadVirtualFields($className);
    }

    /**
     * @param string $className
     */
    private function ensureVirtualFieldQueriesInitialized($className): void
    {
        if (isset($this->virtualFieldQueries[$className])) {
            return;
        }

        $this->ensureVirtualFieldsInitialized($className);
        if (isset($this->virtualFields[$className])) {
            $this->virtualFieldQueries[$className] = $this->loadVirtualFieldQueries($this->virtualFields[$className]);
        }
    }

    public function getEnumCode(string $className, string $fieldName): ?string
    {
        return $this->configManager->getFieldConfig('enum', $className, $fieldName)?->get('enum_code');
    }

    public function getFieldType(string $className, string $fieldName): ?string
    {
        return $this->configManager->hasConfig($className, $fieldName)
            ? $this->configManager->getFieldConfig('enum', $className, $fieldName)?->getId()->getFieldType()
            : null;
    }

    /**
     * @return array [associationName => targetFieldName for enum and NULL for multiEnum, ...]
     */
    private function loadVirtualFields(string $className): array
    {
        $result = [];
        $fieldIds = $this->configManager->getIds('extend', $className);
        foreach ($fieldIds as $fieldId) {
            if (!ExtendHelper::isEnumerableType($fieldId->getFieldType())) {
                continue;
            }

            $associationName = $fieldId->getFieldName();
            if (!$this->isFieldAccessible($className, $associationName)) {
                continue;
            }
            $result[$associationName] = ExtendHelper::isSingleEnumType($fieldId->getFieldType())
                ? $this->configManager->getFieldConfig('extend', $className, $associationName)->getId()->getFieldName()
                : null;
        }

        return $result;
    }

    private function isFieldAccessible($className, $fieldName): bool
    {
        $fieldConfig = $this->configManager->getFieldConfig('extend', $className, $fieldName);

        return ExtendHelper::isFieldAccessible($fieldConfig);
    }

    /**
     * @param array $virtualFields [associationName => targetFieldName for enum and NULL for multiEnum, ...]
     *
     * @return array [associationName => query, ...]
     */
    private function loadVirtualFieldQueries(array $virtualFields): array
    {
        $result = [];
        foreach ($virtualFields as $assoc => $targetFieldName) {
            if ($targetFieldName) {
                $result[$assoc] = [
                    'query' => [
                        'select' => [
                            'expr' => sprintf('target.%s', $targetFieldName),
                            'return_type' => 'enum',
                            'filter_by_id' => true
                        ],
                        'join' => [
                            'left' => [
                                [
                                    'join' => EnumOption::class,
                                    'conditionType' => Join::WITH,
                                    'alias' => 'target',
                                    'condition' => "JSON_EXTRACT(entity.serialized_data, '" . $assoc . "') = target"
                                ]
                            ]
                        ]
                    ]
                ];
            } else {
                $result[$assoc] = [
                    'query' => [
                        'select' => [
                            'expr' => sprintf(
                                "JSON_EXTRACT(entity.serialized_data, '%s') AS %s",
                                $assoc,
                                $assoc
                            ),
                            'return_type' => 'multiEnum',
                            'filter_by_id' => true
                        ]
                    ]
                ];
            }
        }

        return $result;
    }
}

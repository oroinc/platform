<?php

namespace Oro\Bundle\SearchBundle\Provider;

/**
 * Prepares the search mapping configuration to store in a cache.
 */
class SearchMappingCacheNormalizer
{
    private const array ROUTE_NAME_MAP = [
        'n' => 'name',
        'p' => 'parameters'
    ];
    private const array FIELD_TYPE_MAP = [
        't' => 'text',
        'i' => 'integer',
        'd' => 'datetime',
        'r' => 'decimal'
    ];

    public function __construct(
        private readonly array $nameMap,
        private readonly array $fieldNameMap,
        private readonly string $fieldTypeAttribute
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function normalize(array $config): array
    {
        $result = [];
        $nameMap = array_flip($this->nameMap);
        $routeNameMap = array_flip(self::ROUTE_NAME_MAP);
        $fieldNameMap = array_flip($this->fieldNameMap);
        $fieldTypeMap = array_flip(self::FIELD_TYPE_MAP);
        foreach ($config as $entityClass => $entityConfig) {
            foreach ($entityConfig as $name => $value) {
                $key = $nameMap[$name] ?? $name;
                if ('route' === $name) {
                    foreach ($value as $n => $v) {
                        $result[$entityClass][$key][$routeNameMap[$n] ?? $n] = $v;
                    }
                } elseif ('fields' === $name) {
                    foreach ($value as $fieldKey => $field) {
                        foreach ($field as $n => $v) {
                            $k = $fieldNameMap[$n] ?? $n;
                            if ($this->fieldTypeAttribute === $n) {
                                $result[$entityClass][$key][$fieldKey][$k] = $fieldTypeMap[$v] ?? $v;
                            } else {
                                $result[$entityClass][$key][$fieldKey][$k] = $v;
                            }
                        }
                    }
                } else {
                    $result[$entityClass][$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function denormalize(array $config): array
    {
        $result = [];
        foreach ($config as $entityClass => $entityConfig) {
            foreach ($entityConfig as $key => $value) {
                $name = $this->nameMap[$key] ?? $key;
                if ('route' === $name) {
                    foreach ($value as $k => $v) {
                        $result[$entityClass][$name][self::ROUTE_NAME_MAP[$k] ?? $k] = $v;
                    }
                } elseif ('fields' === $name) {
                    foreach ($value as $fieldKey => $field) {
                        foreach ($field as $k => $v) {
                            $n = $this->fieldNameMap[$k] ?? $k;
                            if ($this->fieldTypeAttribute === $n) {
                                $result[$entityClass][$name][$fieldKey][$n] = self::FIELD_TYPE_MAP[$v] ?? $v;
                            } else {
                                $result[$entityClass][$name][$fieldKey][$n] = $v;
                            }
                        }
                    }
                } else {
                    $result[$entityClass][$name] = $value;
                }
            }
        }

        return $result;
    }
}

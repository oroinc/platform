<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\SchemaStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\SchemaStorageAwareTrait;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;
use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * This class helps to describe OpenAPI data types.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DataTypeDescribeHelper implements SchemaStorageAwareInterface
{
    use SchemaStorageAwareTrait;

    private const ARRAY_SUFFIX = '[]';
    private const RANGE_SUFFIX = '..';

    private const TYPE_SCALAR = 'scalar';
    private const TYPE_MIXED = 'mixed';

    private const OPENAPI_SIMPLE_DATA_TYPES = [
        Util::TYPE_STRING,
        Util::TYPE_INTEGER,
        Util::TYPE_NUMBER,
        Util::TYPE_BOOLEAN
    ];

    private array $typeMap;
    private array $typeAliases;
    private array $pluralMap;
    private array $patternMap;
    private array $rangeValuePatterns;

    public function __construct(
        array $typeMap,
        array $typeAliases,
        array $pluralMap,
        array $patternMap,
        array $rangeValuePatterns
    ) {
        $this->typeMap = $typeMap;
        $this->typeAliases = $typeAliases;
        $this->pluralMap = $pluralMap;
        $this->patternMap = $patternMap;
        $this->rangeValuePatterns = $rangeValuePatterns;
    }

    public function registerType(
        OA\OpenApi $api,
        ?string $type,
        ?string $pattern = null,
        mixed $defaultValue = null
    ): OA\Schema {
        $types = $this->normalizeType($this->resolveType($type, $pattern));
        if (1 !== \count($types)) {
            return $this->registerCombinedType($api, $types, $defaultValue);
        }

        $dataType = $types[0];
        if (!$this->isSimpleType($dataType)) {
            return $this->registerDataType($api, $dataType, $defaultValue);
        }

        $simpleType = $this->resolveSimpleType($dataType);
        $simpleTypeSchema = $this->schemaStorage->findSchema($api, $simpleType);
        if (null === $simpleTypeSchema) {
            $simpleTypeSchema = $this->schemaStorage->addSchema($api, $simpleType);
            $simpleTypeSchema->type = $simpleType;
        }

        return $simpleTypeSchema;
    }

    public function registerParameterType(
        OA\OpenApi $api,
        OA\Parameter $parameter,
        ?string $type,
        ?string $pattern = null,
        mixed $defaultValue = null
    ): void {
        $type = $this->resolveType($type, $pattern);
        $types = $this->normalizeType($type);
        try {
            $this->setType($api, $parameter, $types, $defaultValue);
        } catch (\Throwable $e) {
            throw new \LogicException(\sprintf(
                'Unexpected type "%s" for the parameter "%s" of the operation "%s". %s',
                $type,
                $parameter->name,
                $parameter->_context->nested->operationId,
                $e->getMessage()
            ));
        }
        if (Generator::isDefault($parameter->schema)) {
            throw new \LogicException(\sprintf(
                'Unexpected type "%s" for the parameter "%s" of the operation "%s".',
                $type,
                $parameter->name,
                $parameter->_context->nested->operationId
            ));
        }
    }

    public function registerHeaderType(
        OA\OpenApi $api,
        OA\Header $header,
        ?string $type,
        ?string $pattern = null
    ): void {
        $type = $this->resolveType($type, $pattern);
        $types = $this->normalizeType($type);
        try {
            $this->setType($api, $header, $types, null);
        } catch (\Throwable $e) {
            throw new \LogicException(\sprintf(
                'Unexpected type "%s" for the header "%s". %s',
                $type,
                $header->header,
                $e->getMessage()
            ));
        }
        if (Generator::isDefault($header->schema)) {
            throw new \LogicException(\sprintf(
                'Unexpected type "%s" for the header "%s".',
                $type,
                $header->header
            ));
        }
    }

    public function registerPropertyType(
        OA\OpenApi $api,
        string $modelName,
        OA\Property $property,
        ?string $type,
        ?string $pattern = null,
        mixed $defaultValue = null
    ): void {
        $type = $this->resolveType($type, $pattern);
        $types = $this->normalizeType($type);
        try {
            $this->setPropertyType($api, $property, $types, $defaultValue);
        } catch (\Throwable $e) {
            throw new \LogicException(\sprintf(
                'Unexpected type "%s" for the property "%s" of the model "%s". %s',
                $type,
                $property->property,
                $modelName,
                $e->getMessage()
            ));
        }
        if (Generator::isDefault($property->type) && Generator::isDefault($property->ref)) {
            throw new \LogicException(\sprintf(
                'Unexpected type "%s" for the property "%s" of the model "%s".',
                $type,
                $property->property,
                $modelName
            ));
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function resolveType(?string $type, ?string $pattern): string
    {
        if ($type && isset($this->typeAliases[$type])) {
            $type = $this->typeAliases[$type];
        }
        if ($pattern && (!$type || Util::TYPE_STRING === $type) && !empty($this->patternMap[$pattern])) {
            $type = $this->patternMap[$pattern];
        }
        if ($type && isset($this->pluralMap[$type])) {
            $type = $this->pluralMap[$type] . self::ARRAY_SUFFIX;
        }
        if (!$type) {
            $type = Util::TYPE_STRING;
        }

        return $type;
    }

    private function normalizeType(string $type): array
    {
        $types = explode(' or ', $type);
        foreach ($types as $i => $item) {
            if (str_starts_with($item, 'array of ')) {
                $dataType = substr($item, 9);
                $types[$i] = ($this->pluralMap[$dataType] ?? $dataType) . self::ARRAY_SUFFIX;
            }
        }
        $numberOfTypes = \count($types);
        if ($numberOfTypes > 1) {
            if ('array' === $types[1]) {
                $types[1] = $types[0] . self::ARRAY_SUFFIX;
            } elseif ('range' === $types[1]) {
                $types[1] = $types[0] . self::RANGE_SUFFIX;
            }
            if ($numberOfTypes > 2) {
                if ('array' === $types[2]) {
                    $types[2] = $types[0] . self::ARRAY_SUFFIX;
                } elseif ('range' === $types[2]) {
                    $types[2] = $types[0] . self::RANGE_SUFFIX;
                }
            }
        }

        return $types;
    }

    private function setType(
        OA\OpenApi $api,
        OA\Parameter|OA\Header $item,
        array $types,
        mixed $defaultValue
    ): void {
        if (1 === \count($types)) {
            $dataType = $types[0];
            if ($this->isSimpleType($dataType)) {
                $item->schema = $this->createSimpleType(
                    $item,
                    $this->resolveSimpleType($dataType),
                    $defaultValue
                );
            } else {
                $item->schema = Util::createSchemaRef(
                    $item,
                    $this->registerDataType($api, $dataType, $defaultValue)->schema
                );
            }
        } else {
            $item->schema = Util::createSchemaRef(
                $item,
                $this->registerCombinedType($api, $types, $defaultValue)->schema
            );
        }
    }

    private function setPropertyType(
        OA\OpenApi $api,
        OA\Property $property,
        array $types,
        mixed $defaultValue
    ): void {
        if (1 === \count($types)) {
            $dataType = $this->resolveDataType($types[0]);
            if ($this->isSimpleType($dataType)) {
                $property->type = $this->resolveSimpleType($dataType);
            } else {
                $property->ref = Util::getSchemaRefPath(
                    $this->registerDataType($api, $dataType, $defaultValue)->schema
                );
            }
        } else {
            $property->ref = Util::getSchemaRefPath(
                $this->registerCombinedType($api, $types, $defaultValue)->schema
            );
        }
    }

    private function isSimpleType(string $dataType): bool
    {
        if (isset($this->typeMap[$dataType])) {
            return
                \count($this->typeMap[$dataType]) === 1
                && \in_array($this->typeMap[$dataType][0], self::OPENAPI_SIMPLE_DATA_TYPES, true);
        }

        return \in_array($dataType, self::OPENAPI_SIMPLE_DATA_TYPES, true);
    }

    private function resolveSimpleType(string $dataType): string
    {
        return $this->typeMap[$dataType][0] ?? $dataType;
    }

    private function resolveDataType(string $dataType): string
    {
        $dataTypeDetailDelimiterPos = strpos($dataType, DataType::DETAIL_DELIMITER);
        if (false !== $dataTypeDetailDelimiterPos) {
            $dataType = substr($dataType, 0, $dataTypeDetailDelimiterPos);
        }

        return $dataType;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function registerDataType(OA\OpenApi $api, string $dataType, mixed $defaultValue = null): OA\Schema
    {
        if (str_ends_with($dataType, self::ARRAY_SUFFIX)) {
            return $this->registerArrayType($api, substr($dataType, 0, -2), $defaultValue);
        }
        if (str_ends_with($dataType, self::RANGE_SUFFIX)) {
            return $this->registerRangeType($api, substr($dataType, 0, -2), $defaultValue);
        }

        $dataTypeName = $this->buildDataTypeName($dataType, $defaultValue);
        $schema = $this->schemaStorage->findSchema($api, $dataTypeName);
        if (null === $schema) {
            $typeInfo = $this->typeMap[$dataType] ?? null;
            if ($typeInfo) {
                $dataType = $typeInfo[0];
            }
            if (\in_array($dataType, self::OPENAPI_SIMPLE_DATA_TYPES, true)) {
                $schema = $this->schemaStorage->addSchema($api, $dataTypeName);
                $this->describeSimpleType(
                    $schema,
                    $dataType,
                    $typeInfo ? ($typeInfo[1] ?? []) : null,
                    $defaultValue
                );
            } elseif (Util::TYPE_OBJECT === $dataType) {
                $schema = $this->schemaStorage->addSchema($api, $dataTypeName);
                $schema->type = Util::TYPE_OBJECT;
                $schema->additionalProperties = true;
            } elseif (Util::TYPE_ARRAY === $dataType) {
                $schema = $this->schemaStorage->addSchema($api, $dataTypeName);
                $schema->type = Util::TYPE_ARRAY;
                $schema->items = Util::createArrayItems(
                    $schema,
                    $this->registerDataType($api, self::TYPE_MIXED)->schema
                );
            } elseif (self::TYPE_SCALAR === $dataType) {
                $schema = $this->schemaStorage->addSchema($api, $dataTypeName);
                $this->describeScalarType($schema);
            } elseif (self::TYPE_MIXED === $dataType) {
                $schema = $this->schemaStorage->addSchema($api, $dataTypeName);
                $this->describeMixedType($api, $schema);
            } else {
                throw new \LogicException(\sprintf(
                    'The data type "%s" is not supported by OpenAPI specification.',
                    $dataType
                ));
            }
        }

        return $schema;
    }

    private function describeSimpleType(
        OA\Schema $schema,
        string $dataType,
        ?array $properties,
        mixed $defaultValue
    ): void {
        $schema->type = $dataType;
        if ($properties) {
            foreach ($properties as $propertyName => $propertyValue) {
                $schema->{$propertyName} = $propertyValue;
            }
        }
        if (null !== $defaultValue) {
            $schema->default = $this->normalizeDefaultValue($dataType, $defaultValue);
        }
    }

    private function describeScalarType(OA\Schema $schema): void
    {
        $types = [];
        foreach (self::OPENAPI_SIMPLE_DATA_TYPES as $dataType) {
            $types[] = Util::createType($schema, $dataType);
        }
        $schema->type = Util::TYPE_OBJECT;
        $schema->oneOf = $types;
        $schema->nullable = true;
    }

    private function describeMixedType(OA\OpenApi $api, OA\Schema $schema): void
    {
        $types = [];
        foreach (self::OPENAPI_SIMPLE_DATA_TYPES as $dataType) {
            $types[] = Util::createType($schema, $dataType);
        }
        $types[] = Util::createSchemaRef($schema, $this->registerDataType($api, Util::TYPE_OBJECT)->schema);
        $types[] = $this->createMixedArrayType($schema);
        $schema->type = Util::TYPE_OBJECT;
        $schema->anyOf = $types;
        $schema->nullable = true;
    }

    private function createMixedArrayType(OA\Schema $parent): OA\Schema
    {
        $schema = Util::createType($parent, Util::TYPE_ARRAY);
        $schema->items = Util::createArrayItems($schema, $parent->schema);

        return $schema;
    }

    private function registerArrayType(OA\OpenApi $api, string $dataType, mixed $defaultValue = null): OA\Schema
    {
        $dataTypeName = $this->buildDataTypeName($dataType . 'Array', $defaultValue);
        $schema = $this->schemaStorage->findSchema($api, $dataTypeName);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, $dataTypeName);
            $schema->type = Util::TYPE_ARRAY;
            if (Util::TYPE_OBJECT === $dataType) {
                $schema->items = Util::createArrayItems(
                    $schema,
                    $this->registerDataType($api, Util::TYPE_OBJECT)->schema
                );
            } elseif ($this->isSimpleType($dataType)) {
                $schema->items = Util::createArrayItems($schema, $this->resolveSimpleType($dataType), true);
            } elseif (self::TYPE_SCALAR === $dataType || self::TYPE_MIXED === $dataType) {
                $schema->items = Util::createArrayItems($schema, $this->registerDataType($api, $dataType)->schema);
            } else {
                $schema->items = Util::createArrayItems($schema, $dataType);
            }
        }

        return $schema;
    }

    private function registerRangeType(OA\OpenApi $api, string $dataType, mixed $defaultValue = null): OA\Schema
    {
        $dataTypeName = $this->buildDataTypeName($dataType . 'Range', $defaultValue);
        $schema = $this->schemaStorage->findSchema($api, $dataTypeName);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, $dataTypeName);
            $schema->type = Util::TYPE_STRING;
            $schema->pattern = $this->getRangePattern($dataType);
            if (null !== $defaultValue) {
                $schema->default = $this->normalizeDefaultValue($dataType, $defaultValue);
            }
        }

        return $schema;
    }

    private function registerCombinedType(OA\OpenApi $api, array $types, mixed $defaultValue = null): OA\Schema
    {
        $unionTypeSuffix = 'Union';
        foreach ($types as $type) {
            if (str_ends_with($type, self::ARRAY_SUFFIX)) {
                $unionTypeSuffix .= 'Array';
            } elseif (str_ends_with($type, self::RANGE_SUFFIX)) {
                $unionTypeSuffix .= 'Range';
            }
        }

        $dataType = $types[0];
        $dataTypeName = $this->buildDataTypeName($dataType . $unionTypeSuffix, $defaultValue);
        $schema = $this->schemaStorage->findSchema($api, $dataTypeName);
        if (null === $schema) {
            $schema = $this->schemaStorage->addSchema($api, $dataTypeName);
            $schemas = [];
            foreach ($types as $type) {
                $schemas[] = $this->isSimpleType($type)
                    ? $this->createSimpleType($schema, $this->resolveSimpleType($type))
                    : Util::createSchemaRef($schema, $this->registerDataType($api, $type)->schema);
            }
            $schema->oneOf = $schemas;
            if (null !== $defaultValue) {
                $schema->default = $this->normalizeDefaultValue($dataType, $defaultValue);
            }
        }

        return $schema;
    }

    private function createSimpleType(
        OA\AbstractAnnotation $parent,
        string $dataType,
        mixed $defaultValue = null
    ): OA\Schema {
        $schema = Util::createType($parent, $dataType);
        if (null !== $defaultValue) {
            $schema->default = $this->normalizeDefaultValue($dataType, $defaultValue);
        }

        return $schema;
    }

    private function buildDataTypeName(string $dataType, mixed $defaultValue): string
    {
        $dataTypeName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $dataType))));
        if (null !== $defaultValue) {
            $dataTypeName .= '_' . str_replace([',', '.'], '_', $defaultValue);
        }

        return $dataTypeName;
    }

    private function normalizeDefaultValue(?string $dataType, mixed $value): mixed
    {
        if (null === $dataType) {
            return $value;
        }

        switch ($this->typeMap[$dataType][0] ?? $dataType) {
            case Util::TYPE_INTEGER:
                return (int)$value;
            case Util::TYPE_BOOLEAN:
                return (bool)$value;
            case Util::TYPE_NUMBER:
                return (float)$value;
            case Util::TYPE_STRING:
                return (string)$value;
            default:
                return $value;
        }
    }

    private function getRangePattern(string $dataType): string
    {
        $rangeValuePattern = $this->rangeValuePatterns[$dataType] ?? null;
        if (!$rangeValuePattern) {
            throw new \LogicException(\sprintf('Cannot build a range pattern for the "%s" data type.', $dataType));
        }

        return $rangeValuePattern . preg_quote('..', '/') . $rangeValuePattern;
    }
}

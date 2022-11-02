<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Nelmio\ApiDocBundle\DataTypes as ApiDocDataTypes;
use Nelmio\ApiDocBundle\Parser\ParserInterface;
use Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Builds definitions of fields from ApiDocMetadata.
 */
class ApiDocMetadataParser implements ParserInterface
{
    private ValueNormalizer $valueNormalizer;
    private RestDocViewDetector $docViewDetector;
    private ApiDocDataTypeConverter $dataTypeConverter;

    public function __construct(
        ValueNormalizer $valueNormalizer,
        RestDocViewDetector $docViewDetector,
        ApiDocDataTypeConverter $dataTypeConverter
    ) {
        $this->valueNormalizer = $valueNormalizer;
        $this->docViewDetector = $docViewDetector;
        $this->dataTypeConverter = $dataTypeConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $item)
    {
        return
            isset($item['options']['direction'], $item['options']['metadata'])
            && $item['options']['metadata'] instanceof ApiDocMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $item)
    {
        /** @var ApiDocMetadata $data */
        $data = $item['options']['metadata'];

        return $this->getApiDocFieldsDefinition(
            $data->getMetadata(),
            $data->getConfig(),
            $data->getAction(),
            $data->getRequestType(),
            'output' === $item['options']['direction']
        );
    }

    /**
     * @return array [field name => [key => value, ...], ...]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getApiDocFieldsDefinition(
        EntityMetadata $metadata,
        EntityDefinitionConfig $config,
        string $action,
        RequestType $requestType,
        bool $isOutput
    ): array {
        $identifiersData = [];
        $metaPropertiesData = [];
        $fieldsData = [];
        $associationsData = [];

        $identifiers = $metadata->getIdentifierFieldNames();
        $isReadOnlyIdentifier = ApiAction::CREATE === $action && $metadata->hasIdentifierGenerator();

        $metaProperties = $metadata->getMetaProperties();
        foreach ($metaProperties as $metaPropertyName => $metaPropertyMetadata) {
            if (!$this->isPropertyApplicable($metaPropertyMetadata, $isOutput)) {
                continue;
            }
            if (ConfigUtil::CLASS_NAME === ($metaPropertyMetadata->getPropertyPath() ?? $metaPropertyName)) {
                continue;
            }
            $metaPropertyData = $this->getMetaPropertyData(
                $metaPropertyMetadata,
                $config->getField($metaPropertyName)
            );
            $metaPropertiesData[$metaPropertyName] = $metaPropertyData;
        }

        $fields = $metadata->getFields();
        foreach ($fields as $fieldName => $fieldMetadata) {
            if ($this->isPropertyApplicable($fieldMetadata, $isOutput)) {
                $fieldData = $this->getFieldData(
                    $fieldMetadata,
                    $config->getField($fieldName)
                );
                $isIdentifier = \in_array($fieldName, $identifiers, true);
                if ($isIdentifier && $isReadOnlyIdentifier) {
                    $fieldData['readonly'] = true;
                }
                if ($isIdentifier) {
                    $identifiersData[$fieldName] = $fieldData;
                } else {
                    $fieldsData[$fieldName] = $fieldData;
                }
            }
        }

        $associations = $metadata->getAssociations();
        foreach ($associations as $associationName => $associationMetadata) {
            if ($this->isPropertyApplicable($associationMetadata, $isOutput)) {
                $associationData = $this->getAssociationData(
                    $associationMetadata,
                    $config->getField($associationName),
                    $requestType
                );
                $isIdentifier = \in_array($associationName, $identifiers, true);
                if ($isIdentifier && $isReadOnlyIdentifier) {
                    $associationData['readonly'] = true;
                }
                if ($isIdentifier) {
                    $identifiersData[$associationName] = $associationData;
                } elseif (isset($associationData['subType'])) {
                    $associationsData[$associationName] = $associationData;
                } else {
                    $fieldsData[$associationName] = $associationData;
                }
            }
        }

        ksort($identifiersData);
        ksort($metaPropertiesData);
        ksort($fieldsData);
        ksort($associationsData);

        return array_merge($identifiersData, $metaPropertiesData, $fieldsData, $associationsData);
    }

    private function isPropertyApplicable(PropertyMetadata $propertyMetadata, bool $isOutput): bool
    {
        return $isOutput
            ? $propertyMetadata->isOutput()
            : $propertyMetadata->isInput();
    }

    private function getMetaPropertyData(MetaPropertyMetadata $metadata, EntityDefinitionFieldConfig $config): array
    {
        $dataType = $this->getApiDocDataType($metadata->getDataType());

        return [
            'description' => $config->getDescription(),
            'required'    => false,
            'dataType'    => $dataType,
            'actualType'  => $dataType
        ];
    }

    private function getFieldData(FieldMetadata $metadata, EntityDefinitionFieldConfig $config): array
    {
        $dataType = $this->getApiDocDataType($metadata->getDataType());

        return [
            'description' => $config->getDescription(),
            'required'    => !$metadata->isNullable(),
            'dataType'    => $dataType,
            'actualType'  => $dataType
        ];
    }

    private function getAssociationData(
        AssociationMetadata $metadata,
        EntityDefinitionFieldConfig $config,
        RequestType $requestType
    ): array {
        $dataType = $this->getApiDocDataType($metadata->getDataType());
        $result = [
            'description' => $config->getDescription(),
            'required'    => !$metadata->isNullable(),
            'dataType'    => $dataType,
            'actualType'  => $dataType
        ];
        if (!DataType::isAssociationAsField($metadata->getDataType())) {
            $result['subType'] = $this->getEntityType($metadata->getTargetClassName(), $requestType);
            $result['actualType'] = $metadata->isCollection()
                ? ApiDocDataTypes::COLLECTION
                : ApiDocDataTypes::MODEL;
        }

        return $result;
    }

    private function getEntityType(string $entityClass, RequestType $requestType): ?string
    {
        return ValueNormalizerUtil::tryConvertToEntityType($this->valueNormalizer, $entityClass, $requestType);
    }

    private function getApiDocDataType(string $dataType): string
    {
        return $this->dataTypeConverter->convertDataType($dataType, $this->docViewDetector->getView());
    }
}

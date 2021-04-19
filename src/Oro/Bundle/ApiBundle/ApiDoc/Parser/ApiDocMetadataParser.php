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
    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var RestDocViewDetector */
    private $docViewDetector;

    /** @var ApiDocDataTypeConverter */
    private $dataTypeConverter;

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
     * @param EntityMetadata         $metadata
     * @param EntityDefinitionConfig $config
     * @param string                 $action
     * @param RequestType            $requestType
     * @param bool                   $isOutput
     *
     * @return array [field name => [key => value, ...], ...]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getApiDocFieldsDefinition(
        EntityMetadata $metadata,
        EntityDefinitionConfig $config,
        $action,
        RequestType $requestType,
        $isOutput
    ) {
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

    /**
     * @param PropertyMetadata $propertyMetadata
     * @param bool             $isOutput
     *
     * @return bool
     */
    private function isPropertyApplicable(PropertyMetadata $propertyMetadata, $isOutput)
    {
        return $isOutput
            ? $propertyMetadata->isOutput()
            : $propertyMetadata->isInput();
    }

    /**
     * @param MetaPropertyMetadata        $metadata
     * @param EntityDefinitionFieldConfig $config
     *
     * @return array
     */
    private function getMetaPropertyData(MetaPropertyMetadata $metadata, EntityDefinitionFieldConfig $config)
    {
        $dataType = $this->getApiDocDataType($metadata->getDataType());

        return [
            'description' => $config->getDescription(),
            'required'    => false,
            'dataType'    => $dataType,
            'actualType'  => $dataType
        ];
    }

    /**
     * @param FieldMetadata               $metadata
     * @param EntityDefinitionFieldConfig $config
     *
     * @return array
     */
    private function getFieldData(FieldMetadata $metadata, EntityDefinitionFieldConfig $config)
    {
        $dataType = $this->getApiDocDataType($metadata->getDataType());

        return [
            'description' => $config->getDescription(),
            'required'    => !$metadata->isNullable(),
            'dataType'    => $dataType,
            'actualType'  => $dataType
        ];
    }

    /**
     * @param AssociationMetadata         $metadata
     * @param EntityDefinitionFieldConfig $config
     * @param RequestType                 $requestType
     *
     * @return array
     */
    private function getAssociationData(
        AssociationMetadata $metadata,
        EntityDefinitionFieldConfig $config,
        RequestType $requestType
    ) {
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

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     *
     * @return string|null
     */
    private function getEntityType($entityClass, RequestType $requestType)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $requestType,
            false
        );
    }

    private function getApiDocDataType(string $dataType): string
    {
        return $this->dataTypeConverter->convertDataType(
            $dataType,
            $this->docViewDetector->getView()
        );
    }
}

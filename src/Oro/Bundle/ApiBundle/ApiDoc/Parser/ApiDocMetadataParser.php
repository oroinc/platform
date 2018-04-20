<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Nelmio\ApiDocBundle\DataTypes as ApiDocDataTypes;
use Nelmio\ApiDocBundle\Parser\ParserInterface;
use Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Builds definitions of fields from ApiDocMetadata.
 */
class ApiDocMetadataParser implements ParserInterface
{
    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var ApiDocDataTypeConverter */
    private $dataTypeConverter;

    /**
     * @param ValueNormalizer         $valueNormalizer
     * @param ApiDocDataTypeConverter $dataTypeConverter
     */
    public function __construct(
        ValueNormalizer $valueNormalizer,
        ApiDocDataTypeConverter $dataTypeConverter
    ) {
        $this->valueNormalizer = $valueNormalizer;
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
     */
    private function getApiDocFieldsDefinition(
        EntityMetadata $metadata,
        EntityDefinitionConfig $config,
        $action,
        RequestType $requestType,
        $isOutput
    ) {
        $identifiersData = [];
        $fieldsData = [];
        $associationsData = [];

        $identifiers = $metadata->getIdentifierFieldNames();
        $isReadOnlyIdentifier = ApiActions::CREATE === $action && $metadata->hasIdentifierGenerator();

        $fields = $metadata->getFields();
        foreach ($fields as $fieldName => $fieldMetadata) {
            if ($this->isPropertyApplicable($fieldMetadata, $isOutput)) {
                $fieldData = $this->getFieldData(
                    $fieldMetadata,
                    $config->getField($fieldName)
                );
                $isIdentifier = in_array($fieldName, $identifiers, true);
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
                $isIdentifier = in_array($associationName, $identifiers, true);
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
        ksort($fieldsData);
        ksort($associationsData);

        return array_merge($identifiersData, $fieldsData, $associationsData);
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
     * @param FieldMetadata               $metadata
     * @param EntityDefinitionFieldConfig $config
     *
     * @return array
     */
    private function getFieldData(FieldMetadata $metadata, EntityDefinitionFieldConfig $config)
    {
        return [
            'description' => $config->getDescription(),
            'required'    => !$metadata->isNullable(),
            'dataType'    => $this->dataTypeConverter->convertDataType($metadata->getDataType())
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
        $result = [
            'description' => $config->getDescription(),
            'required'    => !$metadata->isNullable(),
            'dataType'    => $this->dataTypeConverter->convertDataType($metadata->getDataType())
        ];
        if (!DataType::isAssociationAsField($metadata->getDataType())) {
            $result['subType'] = $this->getEntityType($metadata->getTargetClassName(), $requestType);
            $result['actualType'] = $metadata->isCollection() ? ApiDocDataTypes::COLLECTION : null;
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
}

<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

use Nelmio\ApiDocBundle\DataTypes as ApiDocDataTypes;
use Nelmio\ApiDocBundle\Parser\ParserInterface;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Parse fields definition by given ApiDocMetadata information
 */
class ApiDocMetadataParser implements ParserInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $item)
    {
        return
            isset($item['options']['metadata'])
            && $item['options']['metadata'] instanceof ApiDocMetadata
            && is_a($item['class'], ApiDocMetadata::class, true);
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
            $data->getRequestType()
        );
    }

    /**
     * @param EntityMetadata         $metadata
     * @param EntityDefinitionConfig $config
     * @param string                 $action
     * @param RequestType            $requestType
     *
     * @return array [field name => [key => value, ...], ...]
     */
    protected function getApiDocFieldsDefinition(
        EntityMetadata $metadata,
        EntityDefinitionConfig $config,
        $action,
        RequestType $requestType
    ) {
        $identifiersData = [];
        $fieldsData = [];
        $associationsData = [];

        $identifiers = $metadata->getIdentifierFieldNames();
        $isReadOnlyIdentifier = ApiActions::CREATE === $action && $metadata->hasIdentifierGenerator();

        $fields = $metadata->getFields();
        foreach ($fields as $fieldName => $fieldMetadata) {
            $fieldData = $this->getFieldData(
                $fieldMetadata,
                $config->getField($fieldName)
            );
            if ($isReadOnlyIdentifier && in_array($fieldName, $identifiers, true)) {
                $fieldData['readonly'] = true;
            }

            if (in_array($fieldName, $identifiers, true)) {
                $identifiersData[$fieldName] = $fieldData;
            } else {
                $fieldsData[$fieldName] = $fieldData;
            }
        }

        $associations = $metadata->getAssociations();
        foreach ($associations as $associationName => $associationMetadata) {
            $associationData = $this->getAssociationData(
                $associationMetadata,
                $config->getField($associationName),
                $requestType
            );
            if ($isReadOnlyIdentifier && in_array($associationName, $identifiers, true)) {
                $associationData['readonly'] = true;
            }

            if (in_array($associationName, $identifiers, true)) {
                $identifiersData[$associationName] = $associationData;
            } elseif (isset($associationData['subType'])) {
                $associationsData[$associationName] = $associationData;
            } else {
                $fieldsData[$associationName] = $associationData;
            }
        }

        ksort($identifiersData);
        ksort($fieldsData);
        ksort($associationsData);

        return array_merge($identifiersData, $fieldsData, $associationsData);
    }

    /**
     * @param FieldMetadata               $metadata
     * @param EntityDefinitionFieldConfig $config
     *
     * @return array
     */
    protected function getFieldData(
        FieldMetadata $metadata,
        EntityDefinitionFieldConfig $config
    ) {
        return [
            'description' => $config->getDescription(),
            'required'    => !$metadata->isNullable(),
            'dataType'    => $metadata->getDataType()
        ];
    }

    /**
     * @param AssociationMetadata         $metadata
     * @param EntityDefinitionFieldConfig $config
     * @param RequestType                 $requestType
     *
     * @return array
     */
    protected function getAssociationData(
        AssociationMetadata $metadata,
        EntityDefinitionFieldConfig $config,
        RequestType $requestType
    ) {
        $result = [
            'description' => $config->getDescription(),
            'required'    => !$metadata->isNullable()
        ];
        $dataType = $metadata->getDataType();
        $result['dataType'] = $dataType;
        if (!DataType::isAssociationAsField($dataType)) {
            $result['subType'] = $this->getEntityType($metadata->getTargetClassName(), $requestType);
            $actualType = null;
            if ($metadata->isCollection()) {
                $actualType = ApiDocDataTypes::COLLECTION;
            }
            $result['actualType'] = $actualType;
        }

        return $result;
    }

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     *
     * @return string|null
     */
    protected function getEntityType($entityClass, RequestType $requestType)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $requestType,
            false
        );
    }
}

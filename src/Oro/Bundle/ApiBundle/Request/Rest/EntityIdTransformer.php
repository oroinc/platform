<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * Transforms entity identifier value to and from a string representation used in REST API.
 */
class EntityIdTransformer implements EntityIdTransformerInterface
{
    /** A symbol to separate fields inside the composite identifier */
    private const COMPOSITE_ID_SEPARATOR = ';';

    private ValueNormalizer $valueNormalizer;
    private RequestType $requestType;

    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
        $this->requestType = new RequestType([RequestType::REST]);
    }

    /**
     * {@inheritdoc}
     */
    public function transform(mixed $id, EntityMetadata $metadata): mixed
    {
        return \is_array($id)
            ? http_build_query($id, '', self::COMPOSITE_ID_SEPARATOR)
            : (string)$id;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform(mixed $value, EntityMetadata $metadata): mixed
    {
        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (\count($idFieldNames) === 1) {
            $value = $this->reverseTransformSingleId(
                $value,
                $this->getSingleIdDataType($metadata)
            );
        } else {
            $value = $this->reverseTransformCompositeEntityId($value, $metadata);
        }

        return $value;
    }

    private function getSingleIdDataType(EntityMetadata $metadata): string
    {
        $idFieldNames = $metadata->getIdentifierFieldNames();
        $idField = $metadata->getProperty(reset($idFieldNames));

        return null !== $idField
            ? $idField->getDataType()
            : DataType::STRING;
    }

    private function reverseTransformSingleId(mixed $value, string $dataType): mixed
    {
        if (DataType::STRING === $dataType) {
            return $value;
        }

        return $this->valueNormalizer->normalizeValue($value, $dataType, $this->requestType);
    }

    /**
     * @throws \UnexpectedValueException if the given entity id cannot be normalized
     */
    private function reverseTransformCompositeEntityId(string $entityId, EntityMetadata $metadata): array
    {
        $fieldMap = [];
        foreach ($metadata->getIdentifierFieldNames() as $fieldName) {
            $fieldMap[$fieldName] = $metadata->getProperty($fieldName)->getDataType();
        }

        $normalized = [];
        foreach (explode(self::COMPOSITE_ID_SEPARATOR, $entityId) as $item) {
            $val = explode('=', $item);
            if (\count($val) !== 2) {
                throw new \UnexpectedValueException(sprintf(
                    'Unexpected identifier value "%s" for composite identifier of the entity "%s".',
                    $entityId,
                    $metadata->getClassName()
                ));
            }

            [$key, $val] = $val;
            $val = urldecode($val);

            if (!isset($fieldMap[$key])) {
                throw new \UnexpectedValueException(sprintf(
                    'The entity identifier contains the key "%s" '
                    . 'which is not defined in composite identifier of the entity "%s".',
                    $key,
                    $metadata->getClassName()
                ));
            }

            $dataType = $fieldMap[$key];
            if (DataType::STRING !== $dataType) {
                $val = $this->valueNormalizer->normalizeValue($val, $dataType, $this->requestType);
            }
            $normalized[$key] = $val;

            unset($fieldMap[$key]);
        }
        if (!empty($fieldMap)) {
            throw new \UnexpectedValueException(sprintf(
                'The entity identifier does not contain all keys '
                . 'defined in composite identifier of the entity "%s".',
                $metadata->getClassName()
            ));
        }

        return $normalized;
    }
}

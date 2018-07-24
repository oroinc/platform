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

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var RequestType */
    protected $requestType;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
        $this->requestType = new RequestType([RequestType::REST]);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($id, EntityMetadata $metadata)
    {
        return \is_array($id)
            ? \http_build_query($id, '', self::COMPOSITE_ID_SEPARATOR)
            : (string)$id;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value, EntityMetadata $metadata)
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

    /**
     * @param EntityMetadata $metadata
     *
     * @return string
     */
    protected function getSingleIdDataType(EntityMetadata $metadata)
    {
        $idFieldNames = $metadata->getIdentifierFieldNames();
        $idField = $metadata->getProperty(\reset($idFieldNames));

        return null !== $idField
            ? $idField->getDataType()
            : DataType::STRING;
    }

    /**
     * @param mixed  $value
     * @param string $dataType
     *
     * @return mixed
     */
    protected function reverseTransformSingleId($value, $dataType)
    {
        if (DataType::STRING === $dataType) {
            return $value;
        }

        return $this->valueNormalizer->normalizeValue($value, $dataType, $this->requestType);
    }

    /**
     * @param string         $entityId
     * @param EntityMetadata $metadata
     *
     * @return array
     *
     * @throws \UnexpectedValueException if the given entity id cannot be normalized
     */
    protected function reverseTransformCompositeEntityId($entityId, EntityMetadata $metadata)
    {
        $fieldMap = [];
        foreach ($metadata->getIdentifierFieldNames() as $fieldName) {
            $fieldMap[$fieldName] = $metadata->getProperty($fieldName)->getDataType();
        }

        $normalized = [];
        foreach (\explode(self::COMPOSITE_ID_SEPARATOR, $entityId) as $item) {
            $val = \explode('=', $item);
            if (\count($val) !== 2) {
                throw new \UnexpectedValueException(
                    \sprintf(
                        'Unexpected identifier value "%s" for composite identifier of the entity "%s".',
                        $entityId,
                        $metadata->getClassName()
                    )
                );
            }

            list($key, $val) = $val;
            $val = \urldecode($val);

            if (!isset($fieldMap[$key])) {
                throw new \UnexpectedValueException(
                    \sprintf(
                        'The entity identifier contains the key "%s" '
                        . 'which is not defined in composite identifier of the entity "%s".',
                        $key,
                        $metadata->getClassName()
                    )
                );
            }

            $dataType = $fieldMap[$key];
            if (DataType::STRING !== $dataType) {
                $val = $this->valueNormalizer->normalizeValue($val, $dataType, $this->requestType);
            }
            $normalized[$key] = $val;

            unset($fieldMap[$key]);
        }
        if (!empty($fieldMap)) {
            throw new \UnexpectedValueException(
                \sprintf(
                    'The entity identifier does not contain all keys '
                    . 'defined in composite identifier of the entity "%s".',
                    $metadata->getClassName()
                )
            );
        }

        return $normalized;
    }
}

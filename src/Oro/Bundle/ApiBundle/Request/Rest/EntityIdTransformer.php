<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class EntityIdTransformer implements EntityIdTransformerInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var RequestType */
    protected $requestType;

    /**
     * @param DoctrineHelper  $doctrineHelper
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(DoctrineHelper $doctrineHelper, ValueNormalizer $valueNormalizer)
    {
        $this->doctrineHelper  = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
        $this->requestType     = new RequestType([RequestType::REST]);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($id)
    {
        return is_array($id)
            ? http_build_query($id, '', ',')
            : (string)$id;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($entityClass, $value)
    {
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
            $idFields = $metadata->getIdentifierFieldNames();
            $value    = count($idFields) === 1
                ? $this->reverseTransformSingleId($value, $metadata->getTypeOfField(reset($idFields)))
                : $this->reverseTransformCombinedEntityId($value, $idFields, $metadata);
        }

        return $value;
    }

    /**
     * @param mixed  $value
     * @param string $dataType
     *
     * @return mixed
     */
    protected function reverseTransformSingleId($value, $dataType)
    {
        return $dataType !== DataType::STRING
            ? $this->valueNormalizer->normalizeValue($value, $dataType, $this->requestType)
            : $value;
    }

    /**
     * @param string        $entityId
     * @param string[]      $idFields
     * @param ClassMetadata $metadata
     *
     * @return array
     *
     * @throws \UnexpectedValueException if the given entity id cannot be normalized
     */
    protected function reverseTransformCombinedEntityId($entityId, $idFields, ClassMetadata $metadata)
    {
        $fieldMap   = array_flip($idFields);
        $normalized = [];
        foreach (explode(',', $entityId) as $item) {
            $val = explode('=', $item);
            if (count($val) !== 2) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'Unexpected identifier value "%s" for composite primary key of the entity "%s".',
                        $entityId,
                        $metadata->getName()
                    )
                );
            }

            $key = $val[0];
            $val = $val[1];

            if (!isset($fieldMap[$key])) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'The entity identifier contains the key "%s" '
                        . 'which is not defined in composite primary key of the entity "%s".',
                        $key,
                        $metadata->getName()
                    )
                );
            }

            $dataType         = $metadata->getTypeOfField($key);
            $normalized[$key] = $dataType !== DataType::STRING
                ? $this->valueNormalizer->normalizeValue($val, $dataType, $this->requestType)
                : $val;

            unset($fieldMap[$key]);
        }
        if (!empty($fieldMap)) {
            throw new \UnexpectedValueException(
                sprintf(
                    'The entity identifier does not contain all keys '
                    . 'defined in composite primary key of the entity "%s".',
                    $metadata->getName()
                )
            );
        }

        return $normalized;
    }
}

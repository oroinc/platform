<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RestRequest;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class NormalizeEntityId implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param DoctrineHelper  $doctrineHelper
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(DoctrineHelper $doctrineHelper, ValueNormalizer $valueNormalizer)
    {
        $this->doctrineHelper  = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $entityId = $context->getId();
        if (null === $entityId || !is_string($entityId)) {
            // no entity identifier or it is already normalized
            return;
        }

        $entityClass = $context->getClassName();
        if (!$entityClass || !$this->doctrineHelper->isManageableEntity($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $idFields = $metadata->getIdentifierFieldNames();
        if (count($idFields) === 1) {
            // single identifier
            $context->setId(
                $this->normalizeValue(
                    $entityId,
                    $metadata->getTypeOfField(reset($idFields)),
                    $context->getRequestType()
                )
            );
        } else {
            // combined identifier
            $context->setId(
                $this->normalizeCombinedEntityId(
                    $entityId,
                    $idFields,
                    $metadata,
                    $context->getRequestType()
                )
            );
        }
    }

    /**
     * @param mixed  $value
     * @param string $dataType
     * @param string $requestType
     *
     * @return mixed
     */
    protected function normalizeValue($value, $dataType, $requestType)
    {
        return $dataType !== DataType::STRING
            ? $this->valueNormalizer->normalizeValue($value, $dataType, $requestType)
            : $value;
    }

    /**
     * @param string        $entityId
     * @param string[]      $idFields
     * @param ClassMetadata $metadata
     * @param string        $requestType
     *
     * @return array
     *
     * @throws \UnexpectedValueException if the given entity id cannot be normalized
     */
    protected function normalizeCombinedEntityId($entityId, $idFields, ClassMetadata $metadata, $requestType)
    {
        $fieldMap   = array_flip($idFields);
        $normalized = [];
        foreach (explode(RestRequest::ARRAY_DELIMITER, $entityId) as $item) {
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

            $normalized[$key] = $this->normalizeValue($val, $metadata->getTypeOfField($key), $requestType);

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

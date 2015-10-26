<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class NormalizeEntityId implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
            if ($metadata->getTypeOfField(reset($idFields)) === 'integer') {
                $normalizedId = (int)$entityId;
                if (((string)$normalizedId) !== $entityId) {
                    throw new \RuntimeException(
                        sprintf(
                            'Expected integer identifier value for the entity "%s". Given "%s".',
                            $entityClass,
                            $entityId
                        )
                    );
                }
                $context->setId($normalizedId);
            }
        } else {
            // combined identifier
            $context->setId($this->normalizeCombinedEntityId($entityId, $idFields, $metadata));
        }
    }

    /**
     * @param string        $entityId
     * @param string[]      $idFields
     * @param ClassMetadata $metadata
     *
     * @return array
     *
     * @throws \RuntimeException if the given entity id cannot be normalized
     */
    protected function normalizeCombinedEntityId($entityId, $idFields, ClassMetadata $metadata)
    {
        $fieldMap   = array_flip($idFields);
        $normalized = [];
        foreach (explode(',', $entityId) as $item) {
            $val = explode('=', $item);
            if (count($val) !== 2) {
                throw new \RuntimeException(
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
                throw new \RuntimeException(
                    sprintf(
                        'The entity identifier contains the key "%s" '
                        . 'which is not defined in composite primary key of the entity "%s".',
                        $key,
                        $metadata->getName()
                    )
                );
            }

            if ($metadata->getTypeOfField($key) === 'integer') {
                $normalizedVal = (int)$val;
                if (((string)$normalizedVal) !== $val) {
                    throw new \RuntimeException(
                        sprintf(
                            'Expected integer identifier value for the key "%s" of the entity "%s". Given "%s".',
                            $key,
                            $metadata->getName(),
                            $entityId
                        )
                    );
                }
                $val = (int)$normalizedVal;
            }
            $normalized[$key] = $val;

            unset($fieldMap[$key]);
        }
        if (!empty($fieldMap)) {
            throw new \RuntimeException(
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

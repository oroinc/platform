<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

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
            if ($metadata->getTypeOfField(reset($idFields)) === 'integer') {
                $context->setId((int)$entityId);
            }
        } else {
            $fieldMap   = array_flip($idFields);
            $normalized = [];
            foreach (explode(',', $entityId) as $item) {
                $val = explode('=', $item);
                if (count($val) !== 2) {
                    throw new \RuntimeException(
                        sprintf(
                            'Unexpected identifier value "%s" for composite primary key of the entity "%s".',
                            $entityId,
                            $entityClass
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
                            $entityClass
                        )
                    );
                }

                if ($metadata->getTypeOfField($key) === 'integer') {
                    $val = (int)$val;
                }
                $normalized[$key] = $val;

                unset($fieldMap[$key]);
            }
            if (!empty($fieldMap)) {
                throw new \RuntimeException(
                    sprintf(
                        'The entity identifier does not contain all keys '
                        . 'defined in composite primary key of the entity "%s".',
                        $entityClass
                    )
                );
            }
            $context->setId($normalized);
        }
    }
}

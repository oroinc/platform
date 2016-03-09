<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Loads object that should be deleted.
 */
class LoadData implements ProcessorInterface
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
        /** @var DeleteContext $context */

        if ($context->hasResult()) {
            // result is already built
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $ids = null;
        $entityId = $context->getId();
        $idFields = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        if (count($idFields) === 1) {
            // single identifier
            if (is_array($entityId)) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'The entity identifier cannot be an array because the entity "%s" has single primary key.',
                        $entityClass
                    )
                );
            }
            $ids = $entityId;
        } else {
            // combined identifier
            $ids = [];
            if (!is_array($entityId)) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'The entity identifier must be an array because the entity "%s" has composite primary key.',
                        $entityClass
                    )
                );
            }
            $counter = 1;
            foreach ($idFields as $field) {
                if (!array_key_exists($field, $entityId)) {
                    throw new \UnexpectedValueException(
                        sprintf(
                            'The entity identifier array must have the key "%s" because '
                            . 'the entity "%s" has composite primary key.',
                            $field,
                            $entityClass
                        )
                    );
                }
                $ids[$field] = $entityId[$field];
                $counter++;
            }
        }

        $context->setObject($this->doctrineHelper->getEntityRepositoryForClass($entityClass)->find($ids));
    }
}

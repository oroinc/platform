<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Builds ORM QueryBuilder object that will be used to get an entity by its identifier.
 */
class BuildSingleItemQuery implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var CriteriaConnector */
    protected $criteriaConnector;

    /**
     * @param DoctrineHelper    $doctrineHelper
     * @param CriteriaConnector $criteriaConnector
     */
    public function __construct(DoctrineHelper $doctrineHelper, CriteriaConnector $criteriaConnector)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->criteriaConnector = $criteriaConnector;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');
        $this->criteriaConnector->applyCriteria($query, $criteria);

        $entityId = $context->getId();
        $idFields = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        if (count($idFields) === 1) {
            // single identifier
            if (is_array($entityId)) {
                throw new \RuntimeException(
                    sprintf(
                        'The entity identifier cannot be an array because the entity "%s" has single primary key.',
                        $entityClass
                    )
                );
            }
            $query
                ->andWhere(sprintf('e.%s = :id', reset($idFields)))
                ->setParameter('id', $entityId);
        } else {
            // combined identifier
            if (!is_array($entityId)) {
                throw new \RuntimeException(
                    sprintf(
                        'The entity identifier must be an array because the entity "%s" has composite primary key.',
                        $entityClass
                    )
                );
            }
            $counter = 1;
            foreach ($idFields as $field) {
                if (!array_key_exists($field, $entityId)) {
                    throw new \RuntimeException(
                        sprintf(
                            'The entity identifier array must have the key "%s" because '
                            . 'the entity "%s" has composite primary key.',
                            $field,
                            $entityClass
                        )
                    );
                }
                $query
                    ->andWhere(sprintf('e.%s = :id%d', $field, $counter))
                    ->setParameter(sprintf('id%d', $counter), $entityId[$field]);
                $counter++;
            }
        }
        $context->setQuery($query);
    }
}

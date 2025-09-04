<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Model\LoadEntityIdsQueryExecutorInterface;
use Oro\Bundle\ApiBundle\Model\LoadEntityIdsQueryInterface;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds ORM QueryBuilder object that will be used to get a list of entities.
 */
class BuildQuery implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly LoadEntityIdsQueryExecutorInterface $loadEntityIdsQueryExecutor
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already exist
            return;
        }

        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        if ($context->hasQuery()) {
            $query = $context->getQuery();
            if ($query instanceof LoadEntityIdsQueryInterface) {
                $config = $context->getConfig();
                if (null === $config || \count($config->getIdentifierFieldNames()) !== 1) {
                    throw new RuntimeException('The entity must have one identifier field.');
                }
                $entityIds = $this->loadEntityIdsQueryExecutor->execute($context, fn () => $query->getEntityIds());
                if (null !== $entityIds) {
                    $context->setQuery(
                        $this->doctrineHelper->createQueryBuilder($entityClass, 'e')
                            ->andWhere(\sprintf(
                                'e.%s IN (:ids)',
                                $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass)
                            ))
                            ->setParameter('ids', $entityIds)
                    );
                    $context->setTotalCountCallback(function () use ($context, $query) {
                        return $this->loadEntityIdsQueryExecutor->execute(
                            $context,
                            fn () => $query->getEntityTotalCount()
                        );
                    });
                    $context->set(LoadEntitiesByEntitySerializer::ENTITY_IDS, $entityIds);
                }
            }
        } else {
            $context->setQuery($this->doctrineHelper->createQueryBuilder($entityClass, 'e'));
        }
    }
}

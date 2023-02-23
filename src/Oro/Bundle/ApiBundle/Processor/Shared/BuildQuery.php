<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Exception\InvalidSorterException;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\LoadEntityIdsQueryInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds ORM QueryBuilder object that will be used to get a list of entities.
 */
class BuildQuery implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private FilterNamesRegistry $filterNamesRegistry;

    public function __construct(DoctrineHelper $doctrineHelper, FilterNamesRegistry $filterNamesRegistry)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

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
                $entityIds = $this->getEntityIds($query, $context);
                if (null !== $entityIds) {
                    $context->setQuery(
                        $this->doctrineHelper->createQueryBuilder($entityClass, 'e')
                            ->andWhere(sprintf(
                                'e.%s IN (:ids)',
                                $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass)
                            ))
                            ->setParameter('ids', $entityIds)
                    );
                    $context->setTotalCountCallback(function () use ($query) {
                        return $query->getEntityTotalCount();
                    });
                    $context->set(LoadEntitiesByEntitySerializer::ENTITY_IDS, $entityIds);
                }
            }
        } else {
            $context->setQuery($this->doctrineHelper->createQueryBuilder($entityClass, 'e'));
        }
    }

    private function getEntityIds(LoadEntityIdsQueryInterface $query, Context $context): ?array
    {
        try {
            return $query->getEntityIds();
        } catch (InvalidSorterException $e) {
            $context->addError(
                Error::createValidationError(Constraint::SORT, $e->getMessage())
                    ->setSource(ErrorSource::createByParameter(
                        $this->getSortFilterName($context->getRequestType(), $context->getFilterValues())
                    ))
            );
        }

        return null;
    }

    private function getSortFilterName(RequestType $requestType, FilterValueAccessorInterface $filterValues): string
    {
        $sortFilterName = $this->filterNamesRegistry
            ->getFilterNames($requestType)
            ->getSortFilterName();
        $sortFilterValue = $filterValues->get($sortFilterName);
        if (null === $sortFilterValue) {
            return $sortFilterName;
        }

        return $sortFilterValue->getSourceKey();
    }
}

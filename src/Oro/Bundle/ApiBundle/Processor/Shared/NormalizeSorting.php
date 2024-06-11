<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\Provider\AssociationSortersProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates sorting criteria to be able to use in ORM queries.
 */
class NormalizeSorting implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private AssociationSortersProvider $associationSortersProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AssociationSortersProvider $associationSortersProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->associationSortersProvider = $associationSortersProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $orderings = $criteria->getOrderings();
        if (!$orderings) {
            return;
        }

        $criteria->orderBy($this->normalizeOrderings($orderings, $context));
    }

    private function normalizeOrderings(array $orderings, Context $context): array
    {
        $sorters = $context->getConfigOfSorters();

        $normalizedOrderings = [];
        foreach ($orderings as $fieldName => $direction) {
            $path = explode(ConfigUtil::PATH_DELIMITER, $fieldName);
            $propertyPath = \count($path) > 1
                ? $this->resolveAssociationSorter($path, $context)
                : $this->resolveSorter($fieldName, $sorters);
            $normalizedOrderings[$propertyPath ?? $fieldName] = $direction;
        }

        return $normalizedOrderings;
    }

    private function resolveSorter(string $fieldName, ?SortersConfig $sorters): ?string
    {
        return $sorters?->getField($fieldName)?->getPropertyPath($fieldName);
    }

    private function resolveAssociationSorter(array $path, Context $context): ?string
    {
        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return null;
        }

        $targetFieldName = array_pop($path);
        [$targetSorters, $associations] = $this->associationSortersProvider->getAssociationSorters(
            $path,
            $context,
            $this->doctrineHelper->getEntityMetadataForClass($entityClass)
        );
        if (null === $targetSorters) {
            return null;
        }
        $targetFieldName = $this->resolveSorter($targetFieldName, $targetSorters);
        if (!$targetFieldName) {
            return null;
        }

        return $associations . ConfigUtil::PATH_DELIMITER . $targetFieldName;
    }
}

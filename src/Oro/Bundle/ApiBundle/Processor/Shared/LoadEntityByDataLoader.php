<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Loads entity using a specified data loader.
 */
class LoadEntityByDataLoader implements ProcessorInterface
{
    private DataLoaderInterface $dataLoader;
    private DoctrineHelper $doctrineHelper;
    private EntityClassResolver $entityClassResolver;
    private QueryHintResolverInterface $queryHintResolver;
    private bool $isDataNormalized;

    public function __construct(
        DataLoaderInterface $dataLoader,
        DoctrineHelper $doctrineHelper,
        EntityClassResolver $entityClassResolver,
        QueryHintResolverInterface $queryHintResolver,
        bool $isDataNormalized = true
    ) {
        $this->dataLoader = $dataLoader;
        $this->doctrineHelper = $doctrineHelper;
        $this->entityClassResolver = $entityClassResolver;
        $this->queryHintResolver = $queryHintResolver;
        $this->isDataNormalized = $isDataNormalized;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unsupported query
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // only configured API resources are supported
            return;
        }

        $context->setResult(
            $this->loadNormalizedData($query, $config, $context->getNormalizationContext())
        );

        if ($this->isDataNormalized) {
            // data are already normalized
            $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
        }
    }

    private function loadNormalizedData(
        QueryBuilder $qb,
        EntityDefinitionConfig $config,
        array $normalizationContext
    ): ?array {
        $initialQb = clone $qb;
        $result = $this->loadData($qb, $config, $normalizationContext);
        if (!$result) {
            $notAclProtectedData = $this->getNotAclProtectedQuery($initialQb, $config)
                ->getOneOrNullResult(Query::HYDRATE_ARRAY);
            if ($notAclProtectedData) {
                throw new AccessDeniedException('No access to the entity.');
            }
            $result = null;
        } elseif (\count($result) === 1) {
            $result = reset($result);
        } else {
            throw new RuntimeException('The result must have one or zero items.');
        }

        return $result;
    }

    private function loadData(QueryBuilder $qb, EntityDefinitionConfig $config, array $normalizationContext): array
    {
        $data = $this->dataLoader->loadData($qb, $config, $normalizationContext);
        if (\count($data) === 1) {
            $data = $this->dataLoader->serializeData($data, $config, $normalizationContext);
        }

        return $data;
    }

    private function getNotAclProtectedQuery(QueryBuilder $qb, EntityDefinitionConfig $config): Query
    {
        $entityClass = $this->entityClassResolver->getEntityClass(QueryBuilderUtil::getSingleRootEntity($qb));
        $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        if (\count($idFieldNames) !== 0) {
            $qb->select(QueryBuilderUtil::getSingleRootAlias($qb) . '.' . reset($idFieldNames));
        }

        $query = $qb->getQuery();
        $this->queryHintResolver->resolveHints($query, $config->getHints());

        return $query;
    }
}

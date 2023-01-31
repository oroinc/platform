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
use Oro\Component\EntitySerializer\EntitySerializer;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Loads entity using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadEntityByEntitySerializer implements ProcessorInterface
{
    private EntitySerializer $entitySerializer;
    private DoctrineHelper $doctrineHelper;
    private EntityClassResolver $entityClassResolver;

    public function __construct(
        EntitySerializer $entitySerializer,
        DoctrineHelper $doctrineHelper,
        EntityClassResolver $entityClassResolver
    ) {
        $this->entitySerializer = $entitySerializer;
        $this->doctrineHelper = $doctrineHelper;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritdoc}
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

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }

    private function loadNormalizedData(
        QueryBuilder $qb,
        EntityDefinitionConfig $config,
        array $normalizationContext
    ): ?array {
        $initialQb = clone $qb;
        $result = $this->entitySerializer->serialize($qb, $config, $normalizationContext);
        if (empty($result)) {
            // use a query without ACL protection to check if an entity exists in DB
            $this->prepareNotAclProtectedQueryBuilder($initialQb);
            $notAclProtectedData = $initialQb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
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

    private function prepareNotAclProtectedQueryBuilder(QueryBuilder $qb): void
    {
        $entityClass = $this->entityClassResolver->getEntityClass(QueryBuilderUtil::getSingleRootEntity($qb));
        $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        if (\count($idFieldNames) !== 0) {
            $qb->select(QueryBuilderUtil::getSingleRootAlias($qb) . '.' . reset($idFieldNames));
        }
    }
}

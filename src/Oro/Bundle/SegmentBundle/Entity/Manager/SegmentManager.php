<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SubQueryLimitHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryBuilderRegistry;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Psr\Log\LoggerInterface;

/**
 * Provides useful methods to work with Segment entities.
 */
class SegmentManager
{
    public const PER_PAGE = 20;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var SegmentQueryBuilderRegistry */
    private $queryBuilderRegistry;

    /** @var SubQueryLimitHelper */
    private $subQueryLimitHelper;

    /** @var AclHelper */
    private $aclHelper;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        SegmentQueryBuilderRegistry $queryBuilderRegistry,
        SubQueryLimitHelper $subQueryLimitHelper,
        AclHelper $aclHelper,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->queryBuilderRegistry = $queryBuilderRegistry;
        $this->subQueryLimitHelper = $subQueryLimitHelper;
        $this->aclHelper = $aclHelper;
        $this->logger = $logger;
    }

    /**
     * @return array [segment type name => segment type label, ...]
     */
    public function getSegmentTypeChoices(): array
    {
        $result = [];
        $types = $this->getEntityRepository(SegmentType::class)->findAll();
        foreach ($types as $type) {
            $result[$type->getLabel()] = $type->getName();
        }

        return $result;
    }

    public function getSegmentByEntityName(
        string $entityName,
        ?string $term,
        int $page = 1,
        int $skippedSegmentId = null
    ): array {
        $queryBuilder = $this->getEntityRepository(Segment::class)
            ->createQueryBuilder('segment')
            ->where('segment.entity = :entity')
            ->setParameter('entity', $entityName);

        if ($term) {
            $queryBuilder
                ->andWhere('LOWER(segment.name) LIKE :segmentName')
                ->setParameter('segmentName', sprintf('%%%s%%', strtolower($term)));
        }

        if (null !== $skippedSegmentId) {
            $queryBuilder
                ->andWhere('segment.id <> :skippedSegmentId')
                ->setParameter('skippedSegmentId', $skippedSegmentId);
        }

        $queryBuilder
            ->setFirstResult(QueryBuilderUtil::getPageOffset($page, static::PER_PAGE))
            ->setMaxResults(self::PER_PAGE + 1)
            ->orderBy('segment.name', 'ASC');
        $segments = $this->aclHelper->apply($queryBuilder)->getResult();

        $result = [
            'results' => [],
            'more'    => count($segments) > self::PER_PAGE
        ];
        array_splice($segments, self::PER_PAGE);
        /** @var Segment $segment */
        foreach ($segments as $segment) {
            $result['results'][] = [
                'id'   => 'segment_' . $segment->getId(),
                'text' => $segment->getName(),
                'type' => 'segment'
            ];
        }

        return $result;
    }

    public function findById(int $segmentId): ?Segment
    {
        return $this->getEntityRepository(Segment::class)->find($segmentId);
    }

    public function getSegmentQueryBuilder(Segment $segment): ?QueryBuilder
    {
        $segmentQueryBuilder = $this->queryBuilderRegistry->getQueryBuilder($segment->getType()->getName());
        if (null !== $segmentQueryBuilder) {
            try {
                return $segmentQueryBuilder->getQueryBuilder($segment);
            } catch (InvalidConfigurationException $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
        }

        return null;
    }

    public function filterBySegment(QueryBuilder $queryBuilder, Segment $segment): void
    {
        $segmentQueryBuilder = $this->getSegmentQueryBuilder($segment);
        if (null === $segmentQueryBuilder) {
            return;
        }

        $segmentQueryBuilderRootAliases = $segmentQueryBuilder->getRootAliases();
        $segmentQueryBuilderRootAlias = reset($segmentQueryBuilderRootAliases);

        $queryBuilderRootAliases = $queryBuilder->getRootAliases();
        $queryBuilderRootAlias = reset($queryBuilderRootAliases);

        if ($segment->getType()->getName() === SegmentType::TYPE_DYNAMIC
            && $this->getFromPart($queryBuilder) !== $this->getFromPart($segmentQueryBuilder)
        ) {
            throw new \LogicException(
                'Query Builder "FROM" part should be the same as Segment Query Builder "FROM" part'
            );
        }

        $identifier = $this->getIdentifierFieldName($segment->getEntity());
        $segmentQueryBuilder = $segmentQueryBuilder->select($segmentQueryBuilderRootAlias . '.' . $identifier);
        $subQuery = $this->bindSegmentParametersToQueryBuilder(
            $segmentQueryBuilder,
            $segment,
            $queryBuilder
        );

        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                $queryBuilderRootAlias . '.' . $identifier,
                $subQuery
            )
        );
    }

    public function getEntityQueryBuilder(Segment $segment): ?QueryBuilder
    {
        $entityClass = $segment->getEntity();
        $repository = $this->getEntityRepository($entityClass);
        $identifier = $this->getIdentifierFieldName($entityClass);
        $alias = 'u';
        $qb = $repository->createQueryBuilder($alias);

        $subQuery = $this->getFilterSubQuery($segment, $qb);
        if (null === $subQuery) {
            return null;
        }

        $this->applyOrderByPart($segment, $qb, $alias);

        return $qb->where($qb->expr()->in($alias . '.' . $identifier, $subQuery));
    }

    public function getFilterSubQuery(Segment $segment, QueryBuilder $externalQueryBuilder): ?string
    {
        $queryBuilder = $this->getSegmentQueryBuilder($segment);
        if (null === $queryBuilder) {
            return null;
        }

        if ($segment->isDynamic()) {
            $identifier = $this->getIdentifierFieldName($segment->getEntity());
            $tableAlias = current($queryBuilder->getDQLPart('from'))->getAlias();
            $tableIdentifier = $tableAlias . '.' . $identifier;
            $queryBuilder->resetDQLParts(['select']);
            $queryBuilder->select($tableIdentifier);

            if ($segment->getRecordsLimit()) {
                $queryBuilder = $this->subQueryLimitHelper->setLimit(
                    $queryBuilder,
                    $segment->getRecordsLimit(),
                    $identifier
                );
            }
        }

        return $this->bindSegmentParametersToQueryBuilder(
            $queryBuilder,
            $segment,
            $externalQueryBuilder
        );
    }

    private function applyOrderByPart(Segment $segment, QueryBuilder $qb, string $alias): void
    {
        $segmentQb = $this->getSegmentQueryBuilder($segment);
        if (null === $segmentQb) {
            return;
        }

        /** @var OrderBy[] $orderBy */
        $orderBy = $segmentQb->getDQLPart('orderBy');
        $aliasToReplace = current($segmentQb->getRootAliases());
        foreach ($orderBy as $obj) {
            foreach ($obj->getParts() as $part) {
                $part = str_replace($aliasToReplace, $alias, $part);
                $qb->add('orderBy', $part, true);
            }
        }
    }

    private function getFromPart(QueryBuilder $queryBuilder): ?string
    {
        $from = $queryBuilder->getDQLPart('from');
        if (\is_array($from)) {
            $from = reset($from);
            if ($from instanceof From) {
                return $from->getFrom();
            }
        }

        return null;
    }

    private function getIdentifierFieldName(string $entityClass): string
    {
        return $this->getEntityManager($entityClass)
            ->getClassMetadata($entityClass)
            ->getSingleIdentifierFieldName();
    }

    private function getEntityManager(string $entityClass): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass($entityClass);
    }

    private function getEntityRepository(string $entityClass): EntityRepository
    {
        return $this->doctrine->getRepository($entityClass);
    }

    private function bindSegmentParametersToQueryBuilder(
        QueryBuilder $queryBuilder,
        Segment $segment,
        QueryBuilder $externalQueryBuilder
    ): string {
        $subQuery = $queryBuilder->getDQL();
        /** @var Parameter[] $params */
        $params = $queryBuilder->getParameters();
        foreach ($params as $parameter) {
            // Isolate parameter names for filter per segment, add "_s<segment.id>_" as additional prefix.
            $parameterName = $parameter->getName();
            $segmentParameterName = '_s' . $segment->getId() . '_' . $parameterName;
            $subQuery = preg_replace(
                '/(?<![\w\d])(' . $parameterName . ')(?![\w\d])/',
                $segmentParameterName,
                $subQuery
            );

            $externalQueryBuilder->setParameter(
                $segmentParameterName,
                $parameter->getValue(),
                $parameter->typeWasSpecified() ? $parameter->getType() : null
            );
        }

        return $subQuery;
    }
}

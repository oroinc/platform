<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SubQueryLimitHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryBuilderRegistry;
use Psr\Log\LoggerInterface;

class SegmentManager
{
    const PER_PAGE = 20;

    /** @var EntityManager */
    protected $em;

    /** @var SegmentQueryBuilderRegistry */
    protected $builderRegistry;

    /** @var LoggerInterface */
    protected $logger;

    /** @var SubQueryLimitHelper */
    protected $subqueryLimitHelper;

    /** @var Cache */
    protected $cache;

    /**
     * @param EntityManager $em
     * @param SegmentQueryBuilderRegistry $builderRegistry
     * @param SubQueryLimitHelper $subQueryLimitHelper
     * @param Cache $cache
     */
    public function __construct(
        EntityManager $em,
        SegmentQueryBuilderRegistry $builderRegistry,
        SubQueryLimitHelper $subQueryLimitHelper,
        Cache $cache
    ) {
        $this->em = $em;
        $this->builderRegistry = $builderRegistry;
        $this->subqueryLimitHelper = $subQueryLimitHelper;
        $this->cache = $cache;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get segment types choice list
     *
     * @return array [
     *  key   => segment type name
     *  value => segment type label
     * ]
     */
    public function getSegmentTypeChoices()
    {
        $result = [];
        $types = $this->em->getRepository('OroSegmentBundle:SegmentType')->findAll();
        foreach ($types as $type) {
            $result[$type->getLabel()] = $type->getName();
        }

        return $result;
    }

    /**
     * @param string $entityName
     * @param string $term
     * @param integer $page optional
     * @param null $skippedSegment
     *
     * @return array
     */
    public function getSegmentByEntityName($entityName, $term, $page = 1, $skippedSegment = null)
    {
        $queryBuilder = $this->em->getRepository('OroSegmentBundle:Segment')
            ->createQueryBuilder('segment')
            ->where('segment.entity = :entity')
            ->setParameter('entity', $entityName);

        if (!empty($term)) {
            $queryBuilder
                ->andWhere('segment.name LIKE :segmentName')
                ->setParameter('segmentName', sprintf('%%%s%%', $term));
        }
        if (!empty($skippedSegment)) {
            $queryBuilder
                ->andWhere('segment.id <> :skippedSegment')
                ->setParameter('skippedSegment', $skippedSegment);
        }

        $segments = $queryBuilder
            ->setFirstResult($this->getOffset($page))
            ->setMaxResults(self::PER_PAGE + 1)
            ->orderBy('segment.id')
            ->getQuery()
            ->getResult();

        $result = [
            'results' => [],
            'more' => count($segments) > self::PER_PAGE
        ];
        array_splice($segments, self::PER_PAGE);
        /** @var Segment $segment */
        foreach ($segments as $segment) {
            $result['results'][] = [
                'id' => 'segment_' . $segment->getId(),
                'text' => $segment->getName(),
                'type' => 'segment',
            ];
        }

        return $result;
    }

    /**
     * @param int $segmentId
     *
     * @return Segment|null
     */
    public function findById($segmentId)
    {
        return $this->em->getRepository(Segment::class)->find($segmentId);
    }

    /**
     * @param Segment $segment
     * @return QueryBuilder|null
     */
    public function getSegmentQueryBuilder(Segment $segment)
    {
        $cacheKey = $this->getQBCacheKey($segment);
        if ($this->cache->contains($cacheKey)) {
            return clone $this->cache->fetch($cacheKey);
        }

        $segmentQueryBuilder = $this->builderRegistry->getQueryBuilder($segment->getType()->getName());
        if ($segmentQueryBuilder) {
            try {
                $queryBuilder = $segmentQueryBuilder->getQueryBuilder($segment);
                $this->cache->save($cacheKey, clone $queryBuilder);

                return $queryBuilder;
            } catch (InvalidConfigurationException $e) {
                if ($this->logger) {
                    $this->logger->error($e->getMessage(), ['exception' => $e]);
                }
                return null;
            }
        }

        return null;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Segment $segment
     * @throws \LogicException
     */
    public function filterBySegment(QueryBuilder $queryBuilder, Segment $segment)
    {
        $segmentQueryBuilder = $this->getSegmentQueryBuilder($segment);

        if (!$segmentQueryBuilder) {
            return;
        }

        $segmentQueryBuilderRootAliases = $segmentQueryBuilder->getRootAliases();
        $segmentQueryBuilderRootAlias = reset($segmentQueryBuilderRootAliases);

        $queryBuilderRootAliases = $queryBuilder->getRootAliases();
        $queryBuilderRootAlias = reset($queryBuilderRootAliases);

        if ($segment->getType()->getName() === SegmentType::TYPE_DYNAMIC
            && $this->getQueryBuilderFrom($queryBuilder) !== $this->getQueryBuilderFrom($segmentQueryBuilder)) {
            throw new \LogicException(
                'Query Builder "FROM" part should be the same as Segment Query Builder "FROM" part'
            );
        }

        $identifier = $this->getIdentifierFieldName($segment->getEntity());
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                $queryBuilderRootAlias . '.' . $identifier,
                $segmentQueryBuilder->select($segmentQueryBuilderRootAlias . '.' . $identifier)->getDQL()
            )
        );

        $params = $segmentQueryBuilder->getParameters();

        foreach ($params as $param) {
            $queryBuilder->setParameter($param->getName(), $param->getValue(), $param->getType());
        }
    }

    /**
     * @param Segment $segment
     *
     * @return QueryBuilder|null
     */
    public function getEntityQueryBuilder(Segment $segment)
    {
        $entityClass = $segment->getEntity();
        $repository = $this->em->getRepository($entityClass);
        $identifier = $this->getIdentifierFieldName($entityClass);
        $alias = 'u';
        $qb = $repository->createQueryBuilder($alias);

        $subQuery = $this->getFilterSubQuery($segment, $qb);
        if ($subQuery === null) {
            return null;
        }

        $qb = $this->applyOrderByParts($segment, $qb, $alias);

        return $qb->where($qb->expr()->in($alias . '.' . $identifier, $subQuery));
    }

    /**
     * Applies sorting to QueryBuilder from DynamicSegmentQueryBuilder
     * @param Segment $segment
     * @param QueryBuilder $qb
     * @param string $alias
     * @return QueryBuilder
     */
    private function applyOrderByParts(Segment $segment, QueryBuilder $qb, $alias)
    {
        $cacheKey = $this->getQBCacheKey($segment);
        if ($this->cache->contains($cacheKey)) {
            $segmentQb = clone $this->cache->fetch($cacheKey);
        } else {
            $segmentQueryBuilder = $this->builderRegistry->getQueryBuilder(SegmentType::TYPE_DYNAMIC);
            $segmentQb = $segmentQueryBuilder->getQueryBuilder($segment);
            $this->cache->save($cacheKey, clone $segmentQb);
        }

        $orderBy = $segmentQb->getDQLPart('orderBy');
        $aliasToReplace = current($segmentQb->getRootAliases());

        /** @var OrderBy $obj */
        foreach ($orderBy as $obj) {
            foreach ($obj->getParts() as $part) {
                $part = str_replace($aliasToReplace, $alias, $part);
                $qb->add('orderBy', $part, true);
            }
        }

        return $qb;
    }

    /**
     * @param Segment $segment
     * @param QueryBuilder $externalQueryBuilder
     *
     * @return string|array|null
     */
    public function getFilterSubQuery(Segment $segment, QueryBuilder $externalQueryBuilder)
    {
        $queryBuilder = null;
        $cacheKey = $this->getQBCacheKey($segment);
        if ($this->cache->contains($cacheKey)) {
            $queryBuilder = clone $this->cache->fetch($cacheKey);
        }

        if (!$queryBuilder) {
            $segmentQueryBuilder = $this->builderRegistry->getQueryBuilder($segment->getType()->getName());
            if ($segmentQueryBuilder === null) {
                return null;
            }

            $queryBuilder = $segmentQueryBuilder->getQueryBuilder($segment);
            $this->cache->save($cacheKey, clone $queryBuilder);
        }

        if ($segment->isDynamic()) {
            $identifier = $this->getIdentifierFieldName($segment->getEntity());
            $tableAlias = current($queryBuilder->getDQLPart('from'))->getAlias();
            $tableIdentifier = $tableAlias . '.' . $identifier;
            $queryBuilder->resetDQLParts(['select']);
            $queryBuilder->select($tableIdentifier);

            if ($segment->getRecordsLimit()) {
                $queryBuilder = $this->subqueryLimitHelper->setLimit(
                    $queryBuilder,
                    $segment->getRecordsLimit(),
                    $identifier
                );
            }

            $subQuery = $queryBuilder->getDQL();
        } else {
            $subQuery = $queryBuilder->getDQL();
        }

        /** @var Parameter[] $params */
        $params = $queryBuilder->getParameters();
        foreach ($params as $param) {
            $externalQueryBuilder->setParameter($param->getName(), $param->getValue(), $param->getType());
        }

        return $subQuery;
    }

    /**
     * Get offset by page.
     *
     * @param int $page
     * @return int
     */
    protected function getOffset($page)
    {
        if ($page > 1) {
            return ($page - 1) * SegmentManager::PER_PAGE;
        }

        return 0;
    }

    /**
     * Return Query Builder `FROM` part
     *
     * @param QueryBuilder $queryBuilder
     * @return string|null
     */
    private function getQueryBuilderFrom(QueryBuilder $queryBuilder)
    {
        $from = $queryBuilder->getDQLPart('from');

        if (is_array($from)) {
            $from = reset($from);

            if ($from instanceof From) {
                return $from->getFrom();
            }
        }

        return null;
    }

    /**
     * @param string $className
     * @return string
     */
    private function getIdentifierFieldName($className)
    {
        $metadata = $this->em->getClassMetadata($className);

        return $metadata->getSingleIdentifierFieldName();
    }

    /**
     * @param Segment $segment
     * @return string
     */
    private function getQBCacheKey(Segment $segment)
    {
        if ($segment->getId()) {
            return sprintf('%s:%s', 'qb', $segment->getId());
        }

        return sprintf('%s:%s:%s', 'qb', $segment->getEntity(), $segment->getDefinition());
    }
}

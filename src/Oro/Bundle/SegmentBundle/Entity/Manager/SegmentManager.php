<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryBuilderRegistry;

class SegmentManager
{
    const PER_PAGE = 20;

    /** @var EntityManager */
    protected $em;

    /** @var SegmentQueryBuilderRegistry */
    protected $builderRegistry;

    /**
     * @param EntityManager               $em
     * @param SegmentQueryBuilderRegistry $builderRegistry
     */
    public function __construct(EntityManager $em, SegmentQueryBuilderRegistry $builderRegistry)
    {
        $this->em = $em;
        $this->builderRegistry = $builderRegistry;
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
        $types  = $this->em->getRepository('OroSegmentBundle:SegmentType')->findAll();
        foreach ($types as $type) {
            $result[$type->getName()] = $type->getLabel();
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
                'id'   => 'segment_' . $segment->getId(),
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
     *
     * @return QueryBuilder|null
     */
    public function getEntityQueryBuilder(Segment $segment)
    {
        $repository = $this->em->getRepository($segment->getEntity());
        $qb = $repository->createQueryBuilder('u');

        $subQuery = $this->getFilterSubQuery($segment, $qb);
        if ($subQuery === null) {
            return null;
        }

        return $qb->where($qb->expr()->in('u.id', $subQuery));
    }

    /**
     * @param Segment $segment
     * @param QueryBuilder $externalQueryBuilder
     *
     * @return string|array|null
     */
    public function getFilterSubQuery(Segment $segment, QueryBuilder $externalQueryBuilder)
    {
        $segmentQueryBuilder = $this->builderRegistry->getQueryBuilder($segment->getType()->getName());
        if ($segmentQueryBuilder !== null) {
            $queryBuilder = $segmentQueryBuilder->getQueryBuilder($segment);
            $queryBuilder->setMaxResults($segment->getRecordsLimit());

            if ($segment->isDynamic() && $segment->getRecordsLimit()) {
                $classMetadata = $queryBuilder->getEntityManager()->getClassMetadata($segment->getEntity());
                $identifiers   = $classMetadata->getIdentifier();
                $identifier = reset($identifiers);
                $idsResult = $queryBuilder->getQuery()->getArrayResult();
                $subQuery = array_column($idsResult, $identifier);
            } else {
                $subQuery = $queryBuilder->getDQL();
                /** @var Parameter[] $params */
                $params = $queryBuilder->getParameters();
                foreach ($params as $param) {
                    $externalQueryBuilder->setParameter($param->getName(), $param->getValue(), $param->getType());
                }
            }

            return $subQuery;
        }

        return null;
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
}

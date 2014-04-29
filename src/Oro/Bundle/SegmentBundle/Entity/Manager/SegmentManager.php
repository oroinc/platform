<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentManager
{
    const PER_PAGE = 20;

    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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
        $queryBuilder = $this->em->getRepository("OroSegmentBundle:Segment")
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

        $result = array(
            'results' => array(),
            'more' => count($segments) > self::PER_PAGE
        );
        array_splice($segments, self::PER_PAGE);
        /** @var Segment $segment */
        foreach ($segments as $segment) {
            $result['results'][] = array(
                'id'   => 'segment_' . $segment->getId(),
                'text' => $segment->getName(),
                'type' => 'segment',
            );
        }

        return $result;
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

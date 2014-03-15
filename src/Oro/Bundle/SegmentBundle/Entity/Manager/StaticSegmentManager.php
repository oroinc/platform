<?php

namespace Oro\Bundle\SegmentBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;

class StaticSegmentManager
{
    /** @var EntityManager */
    protected $em;

    /** @var DynamicSegmentQueryBuilder */
    protected $dynamicSegmentQB;

    /**
     * @param EntityManager              $em
     * @param DynamicSegmentQueryBuilder $dynamicSegmentQB
     */
    public function __construct(EntityManager $em, DynamicSegmentQueryBuilder $dynamicSegmentQB)
    {
        $this->em               = $em;
        $this->dynamicSegmentQB = $dynamicSegmentQB;
    }

    /**
     * Runs static repository restriction query and stores it state into snapshot entity
     *
     * @param Segment $segment
     *
     * @throws \LogicException
     */
    public function run(Segment $segment)
    {
        if ($segment->getType()->getName() !== SegmentType::TYPE_STATIC) {
            throw new \LogicException('Only static segments could have snapshots.');
        }

        $this->em->getRepository('OroSegmentBundle:SegmentSnapshot')->removeBySegment($segment);

        $query = $this->dynamicSegmentQB->build($segment);

        var_dump($query->getSQL());die;
    }
}

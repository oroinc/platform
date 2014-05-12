<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SegmentBundle\Entity\Segment;

class StaticSegmentQueryBuilder implements QueryBuilderInterface
{
    /** @var EntityManager */
    protected $em;

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {inheritdoc}
     */
    public function build(Segment $segment)
    {
        $repo = $this->em->getRepository('OroSegmentBundle:SegmentSnapshot');

        return $repo->getIdentifiersSelectQueryBuilder($segment)->getQuery();
    }
}

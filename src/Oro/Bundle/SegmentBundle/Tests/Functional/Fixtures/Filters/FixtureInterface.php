<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Fixtures\Filters;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

interface FixtureInterface
{
    /**
     * @param EntityManager $em
     *
     * @return Segment
     */
    public function createSegment(EntityManager $em);

    /**
     * Creates data in db which will be queried by segment filter
     *
     * @param EntityManager $em
     */
    public function createData(EntityManager $em);

    /**
     * Checks that created data are expected
     *
     * @param \PHPUnit\Framework\Assert $assertions
     * @param array $actualData
     */
    public function assert(\PHPUnit\Framework\Assert $assertions, array $actualData);
}

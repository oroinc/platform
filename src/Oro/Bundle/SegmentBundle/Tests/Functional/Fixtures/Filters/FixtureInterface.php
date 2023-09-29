<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Fixtures\Filters;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;

interface FixtureInterface
{
    public function createSegment(EntityManagerInterface $em): Segment;

    /**
     * Creates data in db which will be queried by segment filter
     */
    public function createData(EntityManagerInterface $em): void;

    /**
     * Checks that created data are expected
     */
    public function assert(\PHPUnit\Framework\Assert $assertions, array $actualData): void;
}

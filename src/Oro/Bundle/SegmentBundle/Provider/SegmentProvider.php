<?php

namespace Oro\Bundle\SegmentBundle\Provider;

use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentProvider
{
    /** @var Segment */
    protected $segment;

    /**
     * @param Segment $segment
     */
    public function setCurrentSegment(Segment $segment)
    {
        $this->segment = $segment;
    }

    /**
     * @return Segment
     */
    public function getCurrentSegment()
    {
        return $this->segment;
    }
}

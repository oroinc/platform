<?php

namespace Oro\Bundle\SegmentBundle\Provider;

use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentProvider
{
    /** @var Segment|Report */
    protected $segment;

    /**
     * @param Segment $segment
     */
    public function setCurrentItem($segment)
    {
        $this->segment = $segment;
    }

    /**
     * @return Segment|Report
     */
    public function getCurrentItem()
    {
        return $this->segment;
    }
}

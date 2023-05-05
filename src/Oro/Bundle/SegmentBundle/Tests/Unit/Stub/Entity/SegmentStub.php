<?php

declare(strict_types=1);

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Stub\Entity;

use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentStub extends Segment
{
    public function __construct(?int $id = null)
    {
        if ($id !== null) {
            $this->id = $id;
        }
    }
}

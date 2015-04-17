<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Stub\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Segment
 *
 * @ORM\Table(name="oro_segment_snapshot")
 * @ORM\Entity()
 */
class SegmentSnapshot
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true, length=255, nullable=false)
     */
    protected $segmentId;
}

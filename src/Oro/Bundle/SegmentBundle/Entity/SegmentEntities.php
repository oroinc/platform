<?php

namespace Oro\Bundle\SegmentBundle\Entity;

/**
 * SegmentEntities
 *
 * @ORM\Table(name="oro_segment_entities")
 * @ORM\Entity()
 */
class SegmentEntities
{
    /**
     * @ORM\Id
     * @ORM\Column(type="smallint", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Segment
     * @ORM\ManyToOne(targetEntity="Segment")
     * @ORM\JoinColumn(name="segment", referencedColumnName="name")
     */
    protected $segment;
}

<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Stub\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Segment
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_segment_snapshot')]
class SegmentSnapshot
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true, nullable: false)]
    protected ?string $segmentId = null;
}

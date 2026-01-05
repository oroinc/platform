<?php

namespace Oro\Bundle\SegmentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;

/**
 * Snapshot of static segment
 */
#[ORM\Entity(repositoryClass: SegmentSnapshotRepository::class)]
#[ORM\Table(name: 'oro_segment_snapshot')]
#[ORM\Index(columns: ['integer_entity_id'], name: 'sgmnt_snpsht_int_entity_idx')]
#[ORM\Index(columns: ['entity_id'], name: 'sgmnt_snpsht_str_entity_idx')]
#[ORM\UniqueConstraint(columns: ['segment_id', 'entity_id'])]
#[ORM\UniqueConstraint(columns: ['segment_id', 'integer_entity_id'])]
#[ORM\HasLifecycleCallbacks]
class SegmentSnapshot
{
    public const ENTITY_REF_FIELD         = 'entityId';
    public const ENTITY_REF_INTEGER_FIELD = 'integerEntityId';

    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::BIGINT)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\Column(name: 'entity_id', type: Types::STRING, nullable: true)]
    protected ?string $entityId = null;

    #[ORM\Column(name: 'integer_entity_id', type: Types::INTEGER, nullable: true)]
    protected ?int $integerEntityId = null;

    #[ORM\ManyToOne(targetEntity: Segment::class)]
    #[ORM\JoinColumn(name: 'segment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Segment $segment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;

    /**
     * Constructor
     */
    public function __construct(Segment $segment)
    {
        $this->segment = $segment;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $entityId
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @return Segment
     */
    public function getSegment()
    {
        return $this->segment;
    }

    public function setCreatedAt(\Datetime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \Datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Pre persist event listener
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return int
     */
    public function getIntegerEntityId()
    {
        return $this->integerEntityId;
    }

    /**
     * @param int $integerEntityId
     */
    public function setIntegerEntityId($integerEntityId)
    {
        $this->integerEntityId = $integerEntityId;
    }
}

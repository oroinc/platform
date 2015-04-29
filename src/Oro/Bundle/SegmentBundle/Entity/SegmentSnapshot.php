<?php

namespace Oro\Bundle\SegmentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Snapshot of static segment
 *
 * @ORM\Table(
 *      name="oro_segment_snapshot",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"segment_id", "entity_id"})
 *      },
 *      indexes={
 *          @ORM\Index(name="sgmnt_snpsht_int_entity_idx", columns={"integer_entity_id"}),
 *          @ORM\Index(name="sgmnt_snpsht_str_entity_idx", columns={"entity_id"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository")
 * @ORM\HasLifecycleCallbacks
 */
class SegmentSnapshot
{
    const ENTITY_REF_FIELD         = 'entityId';
    const ENTITY_REF_INTEGER_FIELD = 'integerEntityId';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_id", type="string", nullable=true)
     */
    protected $entityId;

    /**
     * @var int
     *
     * @ORM\Column(name="integer_entity_id", type="integer", nullable=true)
     */
    protected $integerEntityId;

    /**
     * @var Segment
     *
     * @ORM\ManyToOne(targetEntity="Segment")
     * @ORM\JoinColumn(name="segment_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $segment;

    /**
     * @var \Datetime $created
     *
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * Constructor
     *
     * @param Segment $segment
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

    /**
     * @param \Datetime $createdAt
     */
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
     *
     * @ORM\PrePersist
     */
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

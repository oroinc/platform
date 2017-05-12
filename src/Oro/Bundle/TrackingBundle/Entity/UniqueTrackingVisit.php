<?php

namespace Oro\Bundle\TrackingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\TrackingBundle\Entity\Repository\UniqueTrackingVisitRepository")
 * @ORM\Table(name="oro_tracking_unique_visit", indexes={
 *     @ORM\Index(name="uvisit_action_date_idx", columns={"website_id", "action_date"}),
 *     @ORM\Index(name="uvisit_user_by_date_idx", columns={"user_identifier", "action_date"})
 * })
 */
class UniqueTrackingVisit
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var TrackingWebsite
     *
     * @ORM\ManyToOne(targetEntity="TrackingWebsite")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $trackingWebsite;

    /**
     * @var int
     *
     * @ORM\Column(name="visit_count", type="integer", nullable=false)
     */
    protected $visitCount;

    /**
     * @var string
     *
     * @ORM\Column(name="user_identifier", type="string", length=32)
     */
    protected $userIdentifier;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="action_date", type="date")
     */
    protected $firstActionTime;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return TrackingWebsite
     */
    public function getTrackingWebsite()
    {
        return $this->trackingWebsite;
    }

    /**
     * @param TrackingWebsite|null $trackingWebsite
     * @return $this
     */
    public function setTrackingWebsite(TrackingWebsite $trackingWebsite = null)
    {
        $this->trackingWebsite = $trackingWebsite;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserIdentifier()
    {
        return $this->userIdentifier;
    }

    /**
     * @param string $userIdentifier
     * @return $this
     */
    public function setUserIdentifier($userIdentifier)
    {
        $this->userIdentifier = $userIdentifier;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getFirstActionTime()
    {
        return $this->firstActionTime;
    }

    /**
     * @param \DateTime $firstActionTime
     * @return $this
     */
    public function setFirstActionTime(\DateTime $firstActionTime)
    {
        $this->firstActionTime = $firstActionTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getVisitCount()
    {
        return $this->visitCount;
    }

    /**
     * @param int $visitCount
     * @return $this
     */
    public function setVisitCount($visitCount)
    {
        $this->visitCount = $visitCount;

        return $this;
    }

    public function increaseVisitCount()
    {
        $this->visitCount++;
    }
}

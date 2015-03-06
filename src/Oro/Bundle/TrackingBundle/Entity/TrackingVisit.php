<?php

namespace Oro\Bundle\TrackingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\TrackingBundle\Model\ExtendTrackingVisit;

/**
 * @ORM\Table(name="oro_tracking_visit")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="icon-external-link"
 *      }
 *  }
 * )
 */
class TrackingVisit extends ExtendTrackingVisit
{
    const ENTITY_NAME = 'Oro\Bundle\TrackingBundle\Entity\TrackingVisit';

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
     * @var string
     *
     * @ORM\Column(name="visitor_uid", type="string", length=255)
     */
    protected $visitorUid;

    /**
     * @var string
     *
     * @ORM\Column(name="user_identifier", type="string", length=255)
     */
    protected $userIdentifier;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="first_action_time", type="datetime")
     */
    protected $firstActionTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_action_time", type="datetime")
     */
    protected $lastActionTime;

    /**
     * @var string
     *
     * @ORM\Column(name="parsed_uid", type="integer", length=255)
     */
    protected $parsedUID;

    /**
     * @var integer
     *
     * @ORM\Column(name="parsing_count", type="integer", nullable=true)
     */
    protected $parsingCount;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255, nullable=true)
     */
    protected $ip;

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
     * @param TrackingWebsite $trackingWebsite
     * @return $this
     */
    public function setTrackingWebsite($trackingWebsite)
    {
        $this->trackingWebsite = $trackingWebsite;

        return $this;
    }

    /**
     * @return string
     */
    public function getVisitorUid()
    {
        return $this->visitorUid;
    }

    /**
     * @param string $visitorUid
     * @return $this
     */
    public function setVisitorUid($visitorUid)
    {
        $this->visitorUid = $visitorUid;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return $this
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

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
     * @return \DateTime
     */
    public function getLastActionTime()
    {
        return $this->lastActionTime;
    }

    /**
     * @param \DateTime $lastActionTime
     * @return $this
     */
    public function setLastActionTime(\DateTime $lastActionTime)
    {
        $this->lastActionTime = $lastActionTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getParsingCount()
    {
        return $this->parsingCount;
    }

    /**
     * @param int $parsingCount
     * @return $this
     */
    public function setParsingCount($parsingCount)
    {
        $this->parsingCount = $parsingCount;

        return $this;
    }

    /**
     * @return string
     */
    public function getParsedUID()
    {
        return $this->parsedUID;
    }

    /**
     * @param string $parsedUID
     * @return $this
     */
    public function setParsedUID($parsedUID)
    {
        $this->parsedUID = $parsedUID;

        return $this;
    }
}

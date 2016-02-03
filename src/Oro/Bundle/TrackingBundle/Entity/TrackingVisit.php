<?php

namespace Oro\Bundle\TrackingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\TrackingBundle\Model\ExtendTrackingVisit;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_tracking_visit", indexes={
 *     @ORM\Index(name="visit_visitorUid_idx", columns={"visitor_uid"}),
 *     @ORM\Index(name="visit_userIdentifier_idx", columns={"user_identifier"}),
 *     @ORM\Index(name="website_first_action_time_idx", columns={"website_id", "first_action_time"})
 * })
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
     * @ORM\Column(name="parsed_uid", type="integer", length=255, nullable=false, options={"default"=0})
     */
    protected $parsedUID = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="identifier_detected", type="boolean", nullable=false, options={"default"=false})
     */
    protected $identifierDetected = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="parsing_count", type="integer", nullable=false, options={"default"=0})
     */
    protected $parsingCount = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255, nullable=true)
     */
    protected $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="client", type="string", length=255, nullable=true)
     */
    protected $client;

    /**
     * @var string
     *
     * @ORM\Column(name="client_version", type="string", length=255, nullable=true)
     */
    protected $clientVersion;

    /**
     * @var string
     *
     * @ORM\Column(name="client_type", type="string", length=255, nullable=true)
     */
    protected $clientType;

    /**
     * @var string
     *
     * @ORM\Column(name="os", type="string", length=255, nullable=true)
     */
    protected $os;

    /**
     * @var string
     *
     * @ORM\Column(name="os_version", type="string", length=255, nullable=true)
     */
    protected $osVersion;

    /**
     * @var boolean
     *
     * @ORM\Column(name="desktop", type="boolean", nullable=true)
     */
    protected $desktop;

    /**
     * @var boolean
     *
     * @ORM\Column(name="mobile", type="boolean", nullable=true)
     */
    protected $mobile;

    /**
     * @var boolean
     *
     * @ORM\Column(name="bot", type="boolean", nullable=true)
     */
    protected $bot;

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

    /**
     * @return boolean
     */
    public function isIdentifierDetected()
    {
        return $this->identifierDetected;
    }

    /**
     * @param boolean $identifierDetected
     */
    public function setIdentifierDetected($identifierDetected)
    {
        $this->identifierDetected = $identifierDetected;
    }

    /**
     * @return string
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param string $client
     * @return $this
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return string
     */
    public function getOs()
    {
        return $this->os;
    }

    /**
     * @param string $os
     * @return $this
     */
    public function setOs($os)
    {
        $this->os = $os;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDesktop()
    {
        return $this->desktop;
    }

    /**
     * @param boolean $desktop
     *
     * @return $this
     */
    public function setDesktop($desktop)
    {
        $this->desktop = $desktop;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMobile()
    {
        return $this->mobile;
    }

    /**
     * @param boolean $mobile
     *
     * @return $this
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientVersion()
    {
        return $this->clientVersion;
    }

    /**
     * @param string $clientVersion
     *
     * @return $this
     */
    public function setClientVersion($clientVersion)
    {
        $this->clientVersion = $clientVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientType()
    {
        return $this->clientType;
    }

    /**
     * @param string $clientType
     *
     * @return $this
     */
    public function setClientType($clientType)
    {
        $this->clientType = $clientType;

        return $this;
    }

    /**
     * @return string
     */
    public function getOsVersion()
    {
        return $this->osVersion;
    }

    /**
     * @param string $osVersion
     *
     * @return $this
     */
    public function setOsVersion($osVersion)
    {
        $this->osVersion = $osVersion;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isBot()
    {
        return $this->bot;
    }

    /**
     * @param boolean $bot
     *
     * @return $this
     */
    public function setBot($bot)
    {
        $this->bot = $bot;

        return $this;
    }
}

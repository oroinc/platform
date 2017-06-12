<?php

namespace Oro\Bundle\TrackingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\TrackingBundle\Model\ExtendTrackingEvent;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Table(name="oro_tracking_event", indexes={
 *     @ORM\Index(name="event_name_idx", columns={"name"}),
 *     @ORM\Index(name="event_loggedAt_idx", columns={"logged_at"}),
 *     @ORM\Index(name="event_createdAt_idx", columns={"created_at"}),
 *     @ORM\Index(name="event_parsed_idx", columns={"parsed"}),
 *     @ORM\Index(name="code_idx", columns={"code"})
 * })
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  defaultValues={
 *      "entity"={
 *          "icon"="icon-external-link"
 *      },
 *      "grid"={
 *          "default"="tracking-events-grid"
 *     }
 *  }
 * )
 */
class TrackingEvent extends ExtendTrackingEvent
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
    protected $website;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="float", nullable=true)
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(name="user_identifier", type="string", length=255)
     */
    protected $userIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="text")
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    protected $code;

    /**
     * @var bool
     *
     * @ORM\Column(name="parsed", type="boolean", nullable=false, options={"default"=false})
     */
    protected $parsed = false;

    /**
     * @var TrackingData
     *
     * @ORM\OneToOne(targetEntity="TrackingData", mappedBy="event")
     **/
    protected $eventData;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="logged_at", type="datetime")
     */
    protected $loggedAt;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->parsed = false;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return TrackingEvent
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set value
     *
     * @param float $value
     * @return TrackingEvent
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set userIdentifier
     *
     * @param string $userIdentifier
     * @return TrackingEvent
     */
    public function setUserIdentifier($userIdentifier)
    {
        $this->userIdentifier = $userIdentifier;

        return $this;
    }

    /**
     * Get userIdentifier
     *
     * @return string
     */
    public function getUserIdentifier()
    {
        return $this->userIdentifier;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return TrackingEvent
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return TrackingEvent
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return TrackingEvent
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return TrackingEvent
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set loggedAt
     *
     * @param \DateTime $loggedAt
     * @return TrackingEvent
     */
    public function setLoggedAt($loggedAt)
    {
        $this->loggedAt = $loggedAt;

        return $this;
    }

    /**
     * Get loggedAt
     *
     * @return \DateTime
     */
    public function getLoggedAt()
    {
        return $this->loggedAt;
    }

    /**
     * Set website
     *
     * @param TrackingWebsite $website
     * @return TrackingEvent
     */
    public function setWebsite(TrackingWebsite $website = null)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return TrackingWebsite
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return boolean
     */
    public function isParsed()
    {
        return $this->parsed;
    }

    /**
     * @param boolean $parsed
     * @return $this
     */
    public function setParsed($parsed)
    {
        $this->parsed = $parsed;

        return $this;
    }

    /**
     * @return TrackingData
     */
    public function getEventData()
    {
        return $this->eventData;
    }

    /**
     * @param TrackingData $eventData
     * @return $this
     */
    public function setEventData($eventData)
    {
        $this->eventData = $eventData;

        return $this;
    }
}

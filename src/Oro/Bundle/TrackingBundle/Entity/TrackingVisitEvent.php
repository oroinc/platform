<?php

namespace Oro\Bundle\TrackingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\TrackingBundle\Model\ExtendTrackingVisitEvent;

/**
 * @ORM\Table(name="oro_tracking_visit_event")
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
class TrackingVisitEvent extends ExtendTrackingVisitEvent
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
     * @var TrackingVisit
     *
     * @ORM\ManyToOne(targetEntity="TrackingVisit")
     * @ORM\JoinColumn(name="visit_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $visit;

    /**
     * @var TrackingEventDictionary
     *
     * @ORM\ManyToOne(targetEntity="TrackingEventDictionary", fetch="EXTRA_LAZY", inversedBy="visitEvents")
     * @ORM\JoinColumn(name="event_id", onDelete="CASCADE", referencedColumnName="id")
     */
    protected $event;

    /**
     * @var TrackingEvent
     *
     * @ORM\OneToOne(targetEntity="TrackingEvent", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="web_event_id", referencedColumnName="id")
     */
    protected $webEvent;

    /**
     * @var TrackingWebsite
     *
     * @ORM\ManyToOne(targetEntity="TrackingWebsite")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $website;

    /**
     * @var integer
     *
     * @ORM\Column(name="parsing_count", type="integer", nullable=false, options={"default"=0})
     */
    protected $parsingCount = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return TrackingVisit
     */
    public function getVisit()
    {
        return $this->visit;
    }

    /**
     * @param TrackingVisit $visit
     * @return $this
     */
    public function setVisit($visit)
    {
        $this->visit = $visit;

        return $this;
    }

    /**
     * @return TrackingEvent
     */
    public function getWebEvent()
    {
        return $this->webEvent;
    }

    /**
     * @param TrackingEvent $webEvent
     * @return $this
     */
    public function setWebEvent($webEvent)
    {
        $this->webEvent = $webEvent;

        return $this;
    }

    /**
     * @return TrackingEventDictionary
     * @return $this
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param TrackingEventDictionary $event
     * @return $this
     */
    public function setEvent($event)
    {
        $this->event = $event;

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
     * @return TrackingWebsite
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param TrackingWebsite $website
     * @return $this
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }
}

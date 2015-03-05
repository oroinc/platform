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
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id")
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
     * @return TrackingEventLibrary
     * @return $this
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param TrackingEventLibrary $event
     * @return $this
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }
}

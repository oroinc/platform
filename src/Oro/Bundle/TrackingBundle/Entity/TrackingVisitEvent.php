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
    const EV_VISTIT = 1;
    const EV_LOGIN = 2;
    const EV_ADD_CART = 3;
    const EV_ORDER = 4;
    const EV_LOGOUT = 5;
    const EV_CHECKOUT = 6;
    const EV_REGISTER = 7;

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
     * @var string
     *
     * @ORM\Column(name="event", type="integer")
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
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     */
    public function setVisit($visit)
    {
        $this->visit = $visit;
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param string $event
     */
    public function setEvent($event)
    {
        $this->event = $event;
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
     */
    public function setWebEvent($webEvent)
    {
        $this->webEvent = $webEvent;
    }
}

<?php

namespace Oro\Bundle\TrackingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(name="oro_tracking_event_dictionary")
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
class TrackingEventDictionary
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var TrackingWebsite
     *
     * @ORM\ManyToOne(targetEntity="TrackingWebsite")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $website;

    /**
     * @var TrackingVisitEvent[]
     *
     * @ORM\OneToMany(targetEntity="TrackingVisitEvent", mappedBy="event")
     **/
    protected $visitEvents;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return TrackingVisitEvent[]
     */
    public function getVisitEvents()
    {
        return $this->visitEvents;
    }

    /**
     * @param TrackingVisitEvent[] $visitEvents
     * @return $this
     */
    public function setVisitEvents($visitEvents)
    {
        $this->visitEvents = $visitEvents;

        return $this;
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
}

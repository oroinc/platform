<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractNavigationHistoryItem implements NavigationItemInterface
{
    const NAVIGATION_HISTORY_ITEM_TYPE          = 'history';
    const NAVIGATION_HISTORY_COLUMN_VISITED_AT  = 'visitedAt';
    const NAVIGATION_HISTORY_COLUMN_VISIT_COUNT = 'visitCount';

    /**
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $url
     *
     * @ORM\Column(name="url", type="string", length=1023)
     */
    protected $url;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="text")
     */
    protected $title;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="visited_at", type="datetime")
     */
    protected $visitedAt;

    /**
     * @var \int
     *
     * @ORM\Column(name="visit_count", type="integer")
     */
    protected $visitCount = 0;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @var string $url
     *
     * @ORM\Column(name="route", type="string", length=128)
     */
    protected $route;

    /**
     * @var array
     *
     * @ORM\Column(name="route_parameters", type="array")
     */
    protected $routeParameters = [];

    /**
     * @var int
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=true)
     */
    protected $entityId;

    /**
     * @var AbstractUser $user
     */
    protected $user;

    /**
     * Constructor
     * @param array $values
     */
    public function __construct(array $values = null)
    {
        if (!empty($values)) {
            $this->setValues($values);
        }
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
     * Set url
     *
     * @param  string $url
     *
     * @return NavigationHistoryItem
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
     * @param  string $title
     *
     * @return NavigationHistoryItem
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
     * Set visitedAt
     *
     * @param  \DateTime $visitedAt
     *
     * @return NavigationHistoryItem
     */
    public function setVisitedAt($visitedAt)
    {
        $this->visitedAt = $visitedAt;

        return $this;
    }

    /**
     * Get visitedAt
     *
     * @return \DateTime
     */
    public function getVisitedAt()
    {
        return $this->visitedAt;
    }

    /**
     * Set visitCount
     *
     * @param  int $visitCount
     *
     * @return NavigationHistoryItem
     */
    public function setVisitCount($visitCount)
    {
        $this->visitCount = $visitCount;

        return $this;
    }

    /**
     * Get visitCount
     *
     * @return int
     */
    public function getVisitCount()
    {
        return $this->visitCount;
    }

    /**
     * @param int $entityId
     *
     * @return NavigationHistoryItem
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param string $route
     *
     * @return NavigationHistoryItem
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param array $routeParameters
     *
     * @return NavigationHistoryItem
     */
    public function setRouteParameters($routeParameters)
    {
        $this->routeParameters = $routeParameters;

        return $this;
    }

    /**
     * @return array
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    /**
     * Set user
     *
     * @param  AbstractUser $user
     * @return NavigationHistoryItem
     */
    public function setUser(AbstractUser $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return AbstractUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set entity properties
     *
     * @param array $values
     */
    public function setValues(array $values)
    {
        if (isset($values['title'])) {
            $this->setTitle($values['title']);
        }
        if (isset($values['url'])) {
            $this->setUrl($values['url']);
        }
        if (isset($values['user'])) {
            $this->setUser($values['user']);
        }
        if (isset($values['organization'])) {
            $this->setOrganization($values['organization']);
        }
        if (isset($values['route'])) {
            $this->setRoute($values['route']);
        }
        if (isset($values['routeParameters'])) {
            $this->setRouteParameters($values['routeParameters']);
        }
        if (isset($values['entityId'])) {
            $this->setEntityId($values['entityId']);
        }
    }

    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function doPrePersist()
    {
        $this->visitedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event handler
     */
    public function doUpdate()
    {
        $this->visitedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->visitCount++;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     *
     * @return NavigationHistoryItem
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}

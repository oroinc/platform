<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\NavigationBundle\Model\UrlAwareInterface;
use Oro\Bundle\NavigationBundle\Model\UrlAwareTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractNavigationHistoryItem implements
    NavigationItemInterface,
    OrganizationAwareInterface,
    UrlAwareInterface
{
    use OrganizationAwareTrait;
    use UrlAwareTrait;

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
     * @param string $title
     *
     * @return AbstractNavigationHistoryItem
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param \DateTime $visitedAt
     *
     * @return AbstractNavigationHistoryItem
     */
    public function setVisitedAt($visitedAt)
    {
        $this->visitedAt = $visitedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getVisitedAt()
    {
        return $this->visitedAt;
    }

    /**
     *
     * @param int $visitCount
     *
     * @return AbstractNavigationHistoryItem
     */
    public function setVisitCount($visitCount)
    {
        $this->visitCount = $visitCount;

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
     * @param int $entityId
     *
     * @return AbstractNavigationHistoryItem
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
     * @return AbstractNavigationHistoryItem
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
     * @return AbstractNavigationHistoryItem
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
     * @param AbstractUser $user
     * @return AbstractNavigationHistoryItem
     */
    public function setUser(AbstractUser $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
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
}

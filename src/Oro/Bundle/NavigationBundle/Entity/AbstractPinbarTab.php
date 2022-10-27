<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Base class for pinbar tabs.
 *
 * @ORM\MappedSuperclass
 */
class AbstractPinbarTab implements NavigationItemInterface
{
    /**
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var NavigationItemInterface $item
     */
    protected $item;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="maximized", type="datetime", nullable=true)
     */
    protected $maximized;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title_short", type="string", length=255)
     */
    protected $titleShort;

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
     * Get maximizeDate
     *
     * @return \DateTime
     */
    public function getMaximized()
    {
        return $this->maximized;
    }

    /**
     * Set maximizeDate
     *
     * @param  boolean   $maximizeDate
     * @return PinbarTab
     */
    public function setMaximized($maximizeDate)
    {
        $this->maximized = $maximizeDate ? new \DateTime() : null;

        return $this;
    }

    /**
     * Set user
     *
     * @param  NavigationItemInterface                       $item
     * @return $this
     */
    public function setItem(NavigationItemInterface $item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get user
     *
     * @return NavigationItemInterface
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function doPrePersist()
    {
        $this->maximized = null;
    }

    /**
     * Get user
     *
     * @return AbstractUser
     */
    public function getUser()
    {
        if ($this->getItem()) {
            return $this->getItem()->getUser();
        }

        return null;
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return PinbarTab
     */
    public function setTitle(string $title): AbstractPinbarTab
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param string $titleShort
     *
     * @return PinbarTab
     */
    public function setTitleShort(string $titleShort): AbstractPinbarTab
    {
        $this->titleShort = $titleShort;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitleShort(): ?string
    {
        return $this->titleShort;
    }

    /**
     * Set entity properties
     */
    public function setValues(array $values)
    {
        if (isset($values['maximized'])) {
            $this->setMaximized((bool) $values['maximized']);
        }
        if ($this->getItem()) {
            $this->getItem()->setValues($values);
        }
    }
}

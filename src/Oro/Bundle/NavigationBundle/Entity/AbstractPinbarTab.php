<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Base class for pinbar tabs.
 */
#[ORM\MappedSuperclass]
class AbstractPinbarTab implements NavigationItemInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    protected ?NavigationItemInterface $item = null;

    #[ORM\Column(name: 'maximized', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $maximized = null;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255)]
    protected ?string $title = null;

    #[ORM\Column(name: 'title_short', type: Types::STRING, length: 255)]
    protected ?string $titleShort = null;

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
     */
    #[ORM\PrePersist]
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

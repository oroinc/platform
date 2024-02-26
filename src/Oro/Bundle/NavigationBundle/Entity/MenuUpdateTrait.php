<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Provides basic implementation for entities which implement MenuUpdateInterface.
 */
trait MenuUpdateTrait
{
    use FallbackTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: '`key`', type: Types::STRING, length: 100)]
    protected ?string $key = null;

    #[ORM\Column(name: 'parent_key', type: Types::STRING, length: 100, nullable: true)]
    protected ?string $parentKey = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    protected ?Collection $titles = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    protected ?Collection $descriptions = null;

    #[ORM\Column(name: 'uri', type: Types::STRING, length: 8190, nullable: true)]
    protected ?string $uri = null;

    #[ORM\Column(name: 'menu', type: Types::STRING, length: 100)]
    protected ?string $menu = null;

    #[ORM\ManyToOne(targetEntity: Scope::class)]
    #[ORM\JoinColumn(name: 'scope_id', referencedColumnName: 'id', nullable: false)]
    protected ?Scope $scope = null;

    #[ORM\Column(name: 'icon', type: Types::STRING, length: 150, nullable: true)]
    protected ?string $icon = null;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN)]
    protected ?bool $active = true;

    #[ORM\Column(name: 'priority', type: Types::INTEGER, nullable: true)]
    protected ?int $priority = null;

    #[ORM\Column(name: 'is_divider', type: Types::BOOLEAN)]
    protected ?bool $divider = false;

    /**
     * Marks menu item as custom.
     * Custom is a menu item initially created by a menu update and which exists owing to a menu update.
     */
    #[ORM\Column(name: 'is_custom', type: Types::BOOLEAN)]
    protected ?bool $custom = false;

    /**
     * Marks menu item as synthetic.
     * Synthetic is a menu item that initially created not by a menu update (i.e. non-custom), but should not be lost
     * even if initial menu item does not exist anymore.
     */
    #[ORM\Column(name: 'is_synthetic', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $synthetic = false;

    public function __construct()
    {
        $this->titles = new ArrayCollection();
        $this->descriptions = new ArrayCollection();
    }

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
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return MenuUpdateInterface
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getParentKey()
    {
        return $this->parentKey;
    }

    /**
     * @param string $parentKey
     *
     * @return MenuUpdateInterface
     */
    public function setParentKey($parentKey)
    {
        $this->parentKey = $parentKey;

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return MenuUpdateInterface
     */
    public function addTitle(LocalizedFallbackValue $title)
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $title
     *
     * @return MenuUpdateInterface
     */
    public function removeTitle(LocalizedFallbackValue $title)
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getDescriptions()
    {
        return $this->descriptions;
    }

    /**
     * @param string $value
     * @return MenuUpdateInterface
     */
    public function setDefaultDescription($value)
    {
        $oldValue = $this->getLocalizedFallbackValue($this->descriptions);

        if ($oldValue && $this->descriptions->contains($oldValue)) {
            $this->descriptions->removeElement($oldValue);
        }
        $newValue = new LocalizedFallbackValue();
        $newValue->setText($value);

        if (!$this->descriptions->contains($newValue)) {
            $this->descriptions->add($newValue);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $description
     *
     * @return MenuUpdateInterface
     */
    public function addDescription(LocalizedFallbackValue $description)
    {
        if (!$this->descriptions->contains($description)) {
            $this->descriptions->add($description);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $description
     *
     * @return MenuUpdateInterface
     */
    public function removeDescription(LocalizedFallbackValue $description)
    {
        if ($this->descriptions->contains($description)) {
            $this->descriptions->removeElement($description);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     *
     * @return MenuUpdateInterface
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return string
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * @param string $menu
     *
     * @return MenuUpdateInterface
     */
    public function setMenu($menu)
    {
        $this->menu = $menu;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     *
     * @return MenuUpdateInterface
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     *
     * @return MenuUpdateInterface
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     *
     * @return MenuUpdateInterface
     */
    public function setPriority($priority)
    {
        $this->priority = (int) $priority;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDivider()
    {
        return $this->divider;
    }

    /**
     * @param boolean $divider
     *
     * @return MenuUpdateInterface
     */
    public function setDivider($divider)
    {
        $this->divider = $divider;

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->generateKey();
    }

    public function generateKey()
    {
        if ($this->key === null) {
            $this->key = uniqid('menu_item_');
        }
    }

    /**
     * @return boolean
     */
    public function isCustom()
    {
        return $this->custom;
    }

    /**
     * @param boolean $custom
     *
     * @return MenuUpdateInterface
     */
    public function setCustom($custom)
    {
        $this->custom = $custom;

        return $this;
    }

    /**
     * Synthetic is a non-custom menu item that should remain in tree even if target item does not exist.
     */
    public function isSynthetic(): bool
    {
        return $this->synthetic;
    }

    public function setSynthetic(bool $synthetic): self
    {
        $this->synthetic = $synthetic;

        return $this;
    }

    /**
     * @return Scope
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param Scope $scope
     *
     * @return MenuUpdateInterface
     */
    public function setScope(Scope $scope)
    {
        $this->scope = $scope;

        return $this;
    }
}

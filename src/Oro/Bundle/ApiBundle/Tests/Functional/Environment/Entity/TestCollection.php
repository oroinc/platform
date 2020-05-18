<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_api_coll")
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TestCollection implements TestFrameworkEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\OneToMany(targetEntity="TestCollectionItem",
     *      mappedBy="withOrphanRemovalParent", orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $withOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\OneToMany(targetEntity="TestCollectionItem",
     *      mappedBy="withoutOrphanRemovalParent"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $withoutOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\OneToMany(targetEntity="TestCollectionItem",
     *      mappedBy="lazyWithOrphanRemovalParent", fetch="LAZY", orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $lazyWithOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\OneToMany(targetEntity="TestCollectionItem",
     *      mappedBy="lazyWithoutOrphanRemovalParent", fetch="LAZY"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $lazyWithoutOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\OneToMany(targetEntity="TestCollectionItem",
     *      mappedBy="extraLazyWithOrphanRemovalParent", fetch="EXTRA_LAZY", orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $extraLazyWithOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\OneToMany(targetEntity="TestCollectionItem",
     *      mappedBy="extraLazyWithoutOrphanRemovalParent", fetch="EXTRA_LAZY"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $extraLazyWithoutOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      inversedBy="manyToManyWithOrphanRemovalParents", orphanRemoval=true
     * )
     * @ORM\JoinTable(name="test_api_coll_mtm_or",
     *      joinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $manyToManyWithOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      inversedBy="manyToManyWithoutOrphanRemovalParents"
     * )
     * @ORM\JoinTable(name="test_api_coll_mtm_nor",
     *      joinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $manyToManyWithoutOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      inversedBy="manyToManyLazyWithOrphanRemovalParents", fetch="LAZY", orphanRemoval=true
     * )
     * @ORM\JoinTable(name="test_api_coll_mtm_l_or",
     *      joinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $manyToManyLazyWithOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      inversedBy="manyToManyLazyWithoutOrphanRemovalParents", fetch="LAZY"
     * )
     * @ORM\JoinTable(name="test_api_coll_mtm_l_nor",
     *      joinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $manyToManyLazyWithoutOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      inversedBy="manyToManyExtraLazyWithOrphanRemovalParents", fetch="EXTRA_LAZY", orphanRemoval=true
     * )
     * @ORM\JoinTable(name="test_api_coll_mtm_el_or",
     *      joinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $manyToManyExtraLazyWithOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      inversedBy="manyToManyExtraLazyWithoutOrphanRemovalParents", fetch="EXTRA_LAZY"
     * )
     * @ORM\JoinTable(name="test_api_coll_mtm_el_nor",
     *      joinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $manyToManyExtraLazyWithoutOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      mappedBy="inverseManyToManyWithOrphanRemovalParents", orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $inverseManyToManyWithOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      mappedBy="inverseManyToManyWithoutOrphanRemovalParents"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $inverseManyToManyWithoutOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      mappedBy="inverseManyToManyLazyWithOrphanRemovalParents", fetch="LAZY", orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $inverseManyToManyLazyWithOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      mappedBy="inverseManyToManyLazyWithoutOrphanRemovalParents", fetch="LAZY"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $inverseManyToManyLazyWithoutOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      mappedBy="inverseManyToManyExtraLazyWithOrphanRemovalParents", fetch="EXTRA_LAZY", orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $inverseManyToManyExtraLazyWithOrphanRemovalItems;

    /**
     * @var Collection|TestCollectionItem[]
     *
     * @ORM\ManyToMany(targetEntity="TestCollectionItem",
     *      mappedBy="inverseManyToManyExtraLazyWithoutOrphanRemovalParents", fetch="EXTRA_LAZY"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $inverseManyToManyExtraLazyWithoutOrphanRemovalItems;

    public function __construct()
    {
        $this->withOrphanRemovalItems = new ArrayCollection();
        $this->withoutOrphanRemovalItems = new ArrayCollection();
        $this->lazyWithOrphanRemovalItems = new ArrayCollection();
        $this->lazyWithoutOrphanRemovalItems = new ArrayCollection();
        $this->extraLazyWithOrphanRemovalItems = new ArrayCollection();
        $this->extraLazyWithoutOrphanRemovalItems = new ArrayCollection();
        $this->manyToManyWithOrphanRemovalItems = new ArrayCollection();
        $this->manyToManyWithoutOrphanRemovalItems = new ArrayCollection();
        $this->manyToManyLazyWithOrphanRemovalItems = new ArrayCollection();
        $this->manyToManyLazyWithoutOrphanRemovalItems = new ArrayCollection();
        $this->manyToManyExtraLazyWithOrphanRemovalItems = new ArrayCollection();
        $this->manyToManyExtraLazyWithoutOrphanRemovalItems = new ArrayCollection();
        $this->inverseManyToManyWithOrphanRemovalItems = new ArrayCollection();
        $this->inverseManyToManyWithoutOrphanRemovalItems = new ArrayCollection();
        $this->inverseManyToManyLazyWithOrphanRemovalItems = new ArrayCollection();
        $this->inverseManyToManyLazyWithoutOrphanRemovalItems = new ArrayCollection();
        $this->inverseManyToManyExtraLazyWithOrphanRemovalItems = new ArrayCollection();
        $this->inverseManyToManyExtraLazyWithoutOrphanRemovalItems = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getWithOrphanRemovalItems()
    {
        return $this->withOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setWithOrphanRemovalItems(Collection $items)
    {
        foreach ($items as $item) {
            $item->setWithOrphanRemovalParent($this);
        }
        $this->withOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->withOrphanRemovalItems->contains($item)) {
            $this->withOrphanRemovalItems->add($item);
            $item->setWithOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->withOrphanRemovalItems->contains($item)) {
            $this->withOrphanRemovalItems->removeElement($item);
            $item->setWithOrphanRemovalParent(null);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getWithoutOrphanRemovalItems()
    {
        return $this->withoutOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setWithoutOrphanRemovalItems(Collection $items)
    {
        foreach ($items as $item) {
            $item->setWithoutOrphanRemovalParent($this);
        }
        $this->withoutOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->withoutOrphanRemovalItems->contains($item)) {
            $this->withoutOrphanRemovalItems->add($item);
            $item->setWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->withoutOrphanRemovalItems->contains($item)) {
            $this->withoutOrphanRemovalItems->removeElement($item);
            $item->setWithoutOrphanRemovalParent(null);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getLazyWithOrphanRemovalItems()
    {
        return $this->lazyWithOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setLazyWithOrphanRemovalItems(Collection $items)
    {
        foreach ($items as $item) {
            $item->setLazyWithOrphanRemovalParent($this);
        }
        $this->lazyWithOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->lazyWithOrphanRemovalItems->contains($item)) {
            $this->lazyWithOrphanRemovalItems->add($item);
            $item->setLazyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->lazyWithOrphanRemovalItems->contains($item)) {
            $this->lazyWithOrphanRemovalItems->removeElement($item);
            $item->setLazyWithOrphanRemovalParent(null);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getLazyWithoutOrphanRemovalItems()
    {
        return $this->lazyWithoutOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setLazyWithoutOrphanRemovalItems(Collection $items)
    {
        foreach ($items as $item) {
            $item->setLazyWithoutOrphanRemovalParent($this);
        }
        $this->lazyWithoutOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->lazyWithoutOrphanRemovalItems->contains($item)) {
            $this->lazyWithoutOrphanRemovalItems->add($item);
            $item->setLazyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->lazyWithoutOrphanRemovalItems->contains($item)) {
            $this->lazyWithoutOrphanRemovalItems->removeElement($item);
            $item->setLazyWithoutOrphanRemovalParent(null);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getExtraLazyWithOrphanRemovalItems()
    {
        return $this->extraLazyWithOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setExtraLazyWithOrphanRemovalItems(Collection $items)
    {
        foreach ($items as $item) {
            $item->setExtraLazyWithOrphanRemovalParent($this);
        }
        $this->extraLazyWithOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addExtraLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->extraLazyWithOrphanRemovalItems->contains($item)) {
            $this->extraLazyWithOrphanRemovalItems->add($item);
            $item->setExtraLazyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeExtraLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->extraLazyWithOrphanRemovalItems->contains($item)) {
            $this->extraLazyWithOrphanRemovalItems->removeElement($item);
            $item->setExtraLazyWithOrphanRemovalParent(null);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getExtraLazyWithoutOrphanRemovalItems()
    {
        return $this->extraLazyWithoutOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setExtraLazyWithoutOrphanRemovalItems(Collection $items)
    {
        foreach ($items as $item) {
            $item->setExtraLazyWithoutOrphanRemovalParent($this);
        }
        $this->extraLazyWithoutOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addExtraLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->extraLazyWithoutOrphanRemovalItems->contains($item)) {
            $this->extraLazyWithoutOrphanRemovalItems->add($item);
            $item->setExtraLazyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeExtraLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->extraLazyWithoutOrphanRemovalItems->contains($item)) {
            $this->extraLazyWithoutOrphanRemovalItems->removeElement($item);
            $item->setExtraLazyWithoutOrphanRemovalParent(null);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getManyToManyWithOrphanRemovalItems()
    {
        return $this->manyToManyWithOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setManyToManyWithOrphanRemovalItems(Collection $items)
    {
        $this->manyToManyWithOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addManyToManyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyWithOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyWithOrphanRemovalItems()->add($item);
            $item->addManyToManyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeManyToManyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getManyToManyWithOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyWithOrphanRemovalItems()->removeElement($item);
            $item->removeManyToManyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getManyToManyWithoutOrphanRemovalItems()
    {
        return $this->manyToManyWithoutOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setManyToManyWithoutOrphanRemovalItems(Collection $items)
    {
        $this->manyToManyWithoutOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addManyToManyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyWithoutOrphanRemovalItems()->add($item);
            $item->addManyToManyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeManyToManyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getManyToManyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyWithoutOrphanRemovalItems()->removeElement($item);
            $item->removeManyToManyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getManyToManyLazyWithOrphanRemovalItems()
    {
        return $this->manyToManyLazyWithOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setManyToManyLazyWithOrphanRemovalItems(Collection $items)
    {
        $this->manyToManyLazyWithOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addManyToManyLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyLazyWithOrphanRemovalItems()->add($item);
            $item->addManyToManyLazyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeManyToManyLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getManyToManyLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyLazyWithOrphanRemovalItems()->removeElement($item);
            $item->removeManyToManyLazyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getManyToManyLazyWithoutOrphanRemovalItems()
    {
        return $this->manyToManyLazyWithoutOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setManyToManyLazyWithoutOrphanRemovalItems(Collection $items)
    {
        $this->manyToManyLazyWithoutOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addManyToManyLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyLazyWithoutOrphanRemovalItems()->add($item);
            $item->addManyToManyLazyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeManyToManyLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getManyToManyLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyLazyWithoutOrphanRemovalItems()->removeElement($item);
            $item->removeManyToManyLazyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getManyToManyExtraLazyWithOrphanRemovalItems()
    {
        return $this->manyToManyExtraLazyWithOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setManyToManyExtraLazyWithOrphanRemovalItems(Collection $items)
    {
        $this->manyToManyExtraLazyWithOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addManyToManyExtraLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyExtraLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyExtraLazyWithOrphanRemovalItems()->add($item);
            $item->addManyToManyExtraLazyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeManyToManyExtraLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getManyToManyExtraLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyExtraLazyWithOrphanRemovalItems()->removeElement($item);
            $item->removeManyToManyExtraLazyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getManyToManyExtraLazyWithoutOrphanRemovalItems()
    {
        return $this->manyToManyExtraLazyWithoutOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setManyToManyExtraLazyWithoutOrphanRemovalItems(Collection $items)
    {
        $this->manyToManyExtraLazyWithoutOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addManyToManyExtraLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyExtraLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyExtraLazyWithoutOrphanRemovalItems()->add($item);
            $item->addManyToManyExtraLazyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeManyToManyExtraLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getManyToManyExtraLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyExtraLazyWithoutOrphanRemovalItems()->removeElement($item);
            $item->removeManyToManyExtraLazyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getInverseManyToManyWithOrphanRemovalItems()
    {
        return $this->inverseManyToManyWithOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setInverseManyToManyWithOrphanRemovalItems(Collection $items)
    {
        $this->inverseManyToManyWithOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addInverseManyToManyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyWithOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyWithOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeInverseManyToManyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getInverseManyToManyWithOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyWithOrphanRemovalItems()->removeElement($item);
            $item->removeInverseManyToManyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getInverseManyToManyWithoutOrphanRemovalItems()
    {
        return $this->inverseManyToManyWithoutOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setInverseManyToManyWithoutOrphanRemovalItems(Collection $items)
    {
        $this->inverseManyToManyWithoutOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addInverseManyToManyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyWithoutOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeInverseManyToManyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getInverseManyToManyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyWithoutOrphanRemovalItems()->removeElement($item);
            $item->removeInverseManyToManyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getInverseManyToManyLazyWithOrphanRemovalItems()
    {
        return $this->inverseManyToManyLazyWithOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setInverseManyToManyLazyWithOrphanRemovalItems(Collection $items)
    {
        $this->inverseManyToManyLazyWithOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addInverseManyToManyLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyLazyWithOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyLazyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeInverseManyToManyLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getInverseManyToManyLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyLazyWithOrphanRemovalItems()->removeElement($item);
            $item->removeInverseManyToManyLazyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getInverseManyToManyLazyWithoutOrphanRemovalItems()
    {
        return $this->inverseManyToManyLazyWithoutOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setInverseManyToManyLazyWithoutOrphanRemovalItems(Collection $items)
    {
        $this->inverseManyToManyLazyWithoutOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addInverseManyToManyLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyLazyWithoutOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyLazyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeInverseManyToManyLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getInverseManyToManyLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyLazyWithoutOrphanRemovalItems()->removeElement($item);
            $item->removeInverseManyToManyLazyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getInverseManyToManyExtraLazyWithOrphanRemovalItems()
    {
        return $this->inverseManyToManyExtraLazyWithOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setInverseManyToManyExtraLazyWithOrphanRemovalItems(Collection $items)
    {
        $this->inverseManyToManyExtraLazyWithOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addInverseManyToManyExtraLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyExtraLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyExtraLazyWithOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyExtraLazyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeInverseManyToManyExtraLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getInverseManyToManyExtraLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyExtraLazyWithOrphanRemovalItems()->removeElement($item);
            $item->removeInverseManyToManyExtraLazyWithOrphanRemovalParent($this);
        }
    }

    /**
     * @return Collection|TestCollectionItem[]
     */
    public function getInverseManyToManyExtraLazyWithoutOrphanRemovalItems()
    {
        return $this->inverseManyToManyExtraLazyWithoutOrphanRemovalItems;
    }

    /**
     * @param Collection|TestCollectionItem[] $items
     */
    public function setInverseManyToManyExtraLazyWithoutOrphanRemovalItems(Collection $items)
    {
        $this->inverseManyToManyExtraLazyWithoutOrphanRemovalItems = $items;
    }

    /**
     * @param TestCollectionItem $item
     */
    public function addInverseManyToManyExtraLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyExtraLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyExtraLazyWithoutOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyExtraLazyWithoutOrphanRemovalParent($this);
        }
    }

    /**
     * @param TestCollectionItem $item
     */
    public function removeInverseManyToManyExtraLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getInverseManyToManyExtraLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyExtraLazyWithoutOrphanRemovalItems()->removeElement($item);
            $item->removeInverseManyToManyExtraLazyWithoutOrphanRemovalParent($this);
        }
    }
}

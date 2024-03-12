<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_api_coll')]
class TestCollection implements TestFrameworkEntityInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: true)]
    private ?string $name = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\OneToMany(mappedBy: 'withOrphanRemovalParent', targetEntity: TestCollectionItem::class, orphanRemoval: true)]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $withOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\OneToMany(mappedBy: 'withoutOrphanRemovalParent', targetEntity: TestCollectionItem::class)]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $withoutOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\OneToMany(
        mappedBy: 'lazyWithOrphanRemovalParent',
        targetEntity: TestCollectionItem::class,
        fetch: 'LAZY',
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $lazyWithOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\OneToMany(mappedBy: 'lazyWithoutOrphanRemovalParent', targetEntity: TestCollectionItem::class, fetch: 'LAZY')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $lazyWithoutOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\OneToMany(
        mappedBy: 'extraLazyWithOrphanRemovalParent',
        targetEntity: TestCollectionItem::class,
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $extraLazyWithOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\OneToMany(
        mappedBy: 'extraLazyWithoutOrphanRemovalParent',
        targetEntity: TestCollectionItem::class,
        fetch: 'EXTRA_LAZY'
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $extraLazyWithoutOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollectionItem::class,
        inversedBy: 'manyToManyWithOrphanRemovalParents',
        orphanRemoval: true
    )]
    #[ORM\JoinTable(name: 'test_api_coll_mtm_or')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyWithOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(targetEntity: TestCollectionItem::class, inversedBy: 'manyToManyWithoutOrphanRemovalParents')]
    #[ORM\JoinTable(name: 'test_api_coll_mtm_nor')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyWithoutOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollectionItem::class,
        inversedBy: 'manyToManyLazyWithOrphanRemovalParents',
        fetch: 'LAZY',
        orphanRemoval: true
    )]
    #[ORM\JoinTable(name: 'test_api_coll_mtm_l_or')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyLazyWithOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollectionItem::class,
        inversedBy: 'manyToManyLazyWithoutOrphanRemovalParents',
        fetch: 'LAZY'
    )]
    #[ORM\JoinTable(name: 'test_api_coll_mtm_l_nor')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyLazyWithoutOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollectionItem::class,
        inversedBy: 'manyToManyExtraLazyWithOrphanRemovalParents',
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[ORM\JoinTable(name: 'test_api_coll_mtm_el_or')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyExtraLazyWithOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollectionItem::class,
        inversedBy: 'manyToManyExtraLazyWithoutOrphanRemovalParents',
        fetch: 'EXTRA_LAZY'
    )]
    #[ORM\JoinTable(name: 'test_api_coll_mtm_el_nor')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $manyToManyExtraLazyWithoutOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollectionItem::class,
        mappedBy: 'inverseManyToManyWithOrphanRemovalParents',
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyWithOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(targetEntity: TestCollectionItem::class, mappedBy: 'inverseManyToManyWithoutOrphanRemovalParents')]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyWithoutOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollectionItem::class,
        mappedBy: 'inverseManyToManyLazyWithOrphanRemovalParents',
        fetch: 'LAZY',
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyLazyWithOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollectionItem::class,
        mappedBy: 'inverseManyToManyLazyWithoutOrphanRemovalParents',
        fetch: 'LAZY'
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyLazyWithoutOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollectionItem::class,
        mappedBy: 'inverseManyToManyExtraLazyWithOrphanRemovalParents',
        fetch: 'EXTRA_LAZY',
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyExtraLazyWithOrphanRemovalItems = null;

    /**
     * @var Collection<int, TestCollectionItem>
     */
    #[ORM\ManyToMany(
        targetEntity: TestCollectionItem::class,
        mappedBy: 'inverseManyToManyExtraLazyWithoutOrphanRemovalParents',
        fetch: 'EXTRA_LAZY'
    )]
    #[ORM\OrderBy(['id' => Criteria::ASC])]
    private ?Collection $inverseManyToManyExtraLazyWithoutOrphanRemovalItems = null;

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

    public function addWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->withOrphanRemovalItems->contains($item)) {
            $this->withOrphanRemovalItems->add($item);
            $item->setWithOrphanRemovalParent($this);
        }
    }

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

    public function addWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->withoutOrphanRemovalItems->contains($item)) {
            $this->withoutOrphanRemovalItems->add($item);
            $item->setWithoutOrphanRemovalParent($this);
        }
    }

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

    public function addLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->lazyWithOrphanRemovalItems->contains($item)) {
            $this->lazyWithOrphanRemovalItems->add($item);
            $item->setLazyWithOrphanRemovalParent($this);
        }
    }

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

    public function addLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->lazyWithoutOrphanRemovalItems->contains($item)) {
            $this->lazyWithoutOrphanRemovalItems->add($item);
            $item->setLazyWithoutOrphanRemovalParent($this);
        }
    }

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

    public function addExtraLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->extraLazyWithOrphanRemovalItems->contains($item)) {
            $this->extraLazyWithOrphanRemovalItems->add($item);
            $item->setExtraLazyWithOrphanRemovalParent($this);
        }
    }

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

    public function addExtraLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->extraLazyWithoutOrphanRemovalItems->contains($item)) {
            $this->extraLazyWithoutOrphanRemovalItems->add($item);
            $item->setExtraLazyWithoutOrphanRemovalParent($this);
        }
    }

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

    public function addManyToManyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyWithOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyWithOrphanRemovalItems()->add($item);
            $item->addManyToManyWithOrphanRemovalParent($this);
        }
    }

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

    public function addManyToManyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyWithoutOrphanRemovalItems()->add($item);
            $item->addManyToManyWithoutOrphanRemovalParent($this);
        }
    }

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

    public function addManyToManyLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyLazyWithOrphanRemovalItems()->add($item);
            $item->addManyToManyLazyWithOrphanRemovalParent($this);
        }
    }

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

    public function addManyToManyLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyLazyWithoutOrphanRemovalItems()->add($item);
            $item->addManyToManyLazyWithoutOrphanRemovalParent($this);
        }
    }

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

    public function addManyToManyExtraLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyExtraLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyExtraLazyWithOrphanRemovalItems()->add($item);
            $item->addManyToManyExtraLazyWithOrphanRemovalParent($this);
        }
    }

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

    public function addManyToManyExtraLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getManyToManyExtraLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getManyToManyExtraLazyWithoutOrphanRemovalItems()->add($item);
            $item->addManyToManyExtraLazyWithoutOrphanRemovalParent($this);
        }
    }

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

    public function addInverseManyToManyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyWithOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyWithOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyWithOrphanRemovalParent($this);
        }
    }

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

    public function addInverseManyToManyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyWithoutOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyWithoutOrphanRemovalParent($this);
        }
    }

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

    public function addInverseManyToManyLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyLazyWithOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyLazyWithOrphanRemovalParent($this);
        }
    }

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

    public function addInverseManyToManyLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyLazyWithoutOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyLazyWithoutOrphanRemovalParent($this);
        }
    }

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

    public function addInverseManyToManyExtraLazyWithOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyExtraLazyWithOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyExtraLazyWithOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyExtraLazyWithOrphanRemovalParent($this);
        }
    }

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

    public function addInverseManyToManyExtraLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if (!$this->getInverseManyToManyExtraLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyExtraLazyWithoutOrphanRemovalItems()->add($item);
            $item->addInverseManyToManyExtraLazyWithoutOrphanRemovalParent($this);
        }
    }

    public function removeInverseManyToManyExtraLazyWithoutOrphanRemovalItem(TestCollectionItem $item)
    {
        if ($this->getInverseManyToManyExtraLazyWithoutOrphanRemovalItems()->contains($item)) {
            $this->getInverseManyToManyExtraLazyWithoutOrphanRemovalItems()->removeElement($item);
            $item->removeInverseManyToManyExtraLazyWithoutOrphanRemovalParent($this);
        }
    }
}

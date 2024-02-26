<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Attribute;

#[ORM\Entity]
class Product
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    private ?string $name = null;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'price', type: Types::DECIMAL)]
    private $price;

    #[ORM\Column(name: 'count', type: Types::INTEGER)]
    private ?int $count = null;

    #[ORM\Column(name: 'create_date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createDate = null;

    #[ORM\Column(name: 'description', type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Manufacturer::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'manufacturer_id', referencedColumnName: 'id')]
    private ?Manufacturer $manufacturer = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'products')]
    private ?Collection $categories = null;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|null $name
     *
     * @return Product
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $description
     *
     * @return Product
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param Manufacturer|null $manufacturer
     *
     * @return Product
     */
    public function setManufacturer(Manufacturer $manufacturer = null)
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    /**
     * @return Manufacturer|null
     */
    public function getManufacturer()
    {
        return $this->manufacturer;
    }

    /**
     * @param float|null $price
     *
     * @return Product
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param int|null $count
     *
     * @return Product
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param \DateTime $createDate
     *
     * @return Product
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    public function __toString()
    {
        return (string)$this->name;
    }

    public function getValue($code)
    {
        return new Attribute($code);
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function setRecordId($id)
    {
        $this->id = $id;
    }

    /**
     * @param Category $category
     *
     * @return Product
     */
    public function addCategory(Category $category)
    {
        $this->categories[] = $category;

        return $this;
    }

    public function removeCategory(Category $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * @return Collection|Category[]
     */
    public function getCategories()
    {
        return $this->categories;
    }
}

<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Attribute;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class Product
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var float|null
     *
     * @ORM\Column(name="price", type="decimal")
     */
    private $price;

    /**
     * @var int|null
     *
     * @ORM\Column(name="count", type="integer")
     */
    private $count;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $createDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var Manufacturer|null
     *
     * @ORM\ManyToOne(targetEntity="Manufacturer", inversedBy="products")
     * @ORM\JoinColumn(name="manufacturer_id", referencedColumnName="id")
     */
    private $manufacturer;

    /**
     * @var Collection|Category[]
     *
     * @ORM\MayToMany(targetEntity="Category", inversedBy="products")
     */
    private $categories;

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

<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_table")
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category_name", referencedColumnName="name")
     **/
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="products")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     **/
    protected $owner;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    /** @var ProductPrice */
    protected $price;

    /**
     * @ORM\Column(name="price_value", type="string")
     */
    protected $priceValue;

    /**
     * @ORM\Column(name="price_currency", type="string")
     */
    protected $priceCurrency;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User|null $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return ProductPrice
     */
    public function getPrice()
    {
        if (!$this->price) {
            $this->price = new ProductPrice($this->priceValue, $this->priceCurrency);
        }

        return $this->price;
    }

    /**
     * @param ProductPrice $price
     *
     * @return self
     */
    public function setPrice(ProductPrice $price)
    {
        $this->price = $price;
        $this->priceValue = $this->price->getValue();
        $this->priceCurrency = $this->price->getCurrency();

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }
}

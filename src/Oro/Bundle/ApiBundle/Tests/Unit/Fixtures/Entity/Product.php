<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_table')]
class Product
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 50)]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_name', referencedColumnName: 'name')]
    protected ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    protected ?User $owner = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $updatedAt = null;

    /** @var ProductPrice */
    protected $price;

    #[ORM\Column(name: 'price_value', type: Types::STRING)]
    protected ?string $priceValue = null;

    #[ORM\Column(name: 'price_currency', type: Types::STRING)]
    protected ?string $priceCurrency = null;

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
     * @return ProductPrice|null
     */
    public function getNullablePrice()
    {
        return $this->price;
    }

    /**
     * @param ProductPrice|null $price
     *
     * @return self
     */
    public function setNullablePrice(?ProductPrice $price)
    {
        $this->price = $price;
        if (null === $this->price) {
            $this->priceValue = null;
            $this->priceCurrency = null;
        } else {
            $this->priceValue = $this->price->getValue();
            $this->priceCurrency = $this->price->getCurrency();
        }

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->name;
    }
}

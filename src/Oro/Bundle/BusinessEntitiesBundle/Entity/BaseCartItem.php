<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class BasePerson
 *
 * @package Oro\Bundle\BusinessEntitiesBundle\Entity
 *
 * @ORM\MappedSuperclass
 */
class BaseCartItem
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="BaseCart", inversedBy="cartItems",cascade={"persist"})
     * @ORM\JoinColumn(name="cart_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $cart;

    /**
     * @var string
     *
     * @ORM\Column(name="sku", type="string", length=255)
     */
    protected $sku;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * Qty
     * @var float
     *
     * @ORM\Column(name="qty", type="float")
     */
    protected $qty;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float")
     */
    protected $price;

    /**
     * @var float
     *
     * @ORM\Column(name="discount_amount", type="float")
     */
    protected $discountAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="tax_percent", type="float")
     */
    protected $taxPercent;

    /**
     * @var float
     *
     * @ORM\Column(name="weight", type="float", nullable=true)
     */
    protected $weight;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(type="datetime")
     */
    protected $updatedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $cart
     */
    public function setCart($cart)
    {
        $this->cart = $cart;
    }

    /**
     * @return Cart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $qty
     * @return $this
     */
    public function setQty($qty)
    {
        $this->qty = $qty;
        return $this;
    }

    /**
     * @return float
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @param string $sku
     * @return $this
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
        return $this;
    }

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @param float $weight
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param float $discountAmount
     *
     * @return $this
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->discountAmount = $discountAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * @param float $taxPercent
     *
     * @return $this
     */
    public function setTaxPercent($taxPercent)
    {
        $this->taxPercent = $taxPercent;
        return $this;
    }

    /**
     * @return float
     */
    public function getTaxPercent()
    {
        return $this->taxPercent;
    }
}

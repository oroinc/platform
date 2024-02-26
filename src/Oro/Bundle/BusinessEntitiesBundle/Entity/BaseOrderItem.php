<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base entity for order items
 *
 * @package Oro\Bundle\BusinessEntitiesBundle\Entity
 */
#[ORM\MappedSuperclass]
class BaseOrderItem
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $name = null;

    #[ORM\Column(name: 'sku', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $sku = null;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'qty', type: Types::FLOAT, nullable: false)]
    protected $qty;

    /**
     * @var double
     */
    #[ORM\Column(name: 'cost', type: 'money', nullable: true)]
    protected $cost;

    /**
     * @var double
     */
    #[ORM\Column(name: 'price', type: 'money', nullable: true)]
    protected $price;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'weight', type: Types::FLOAT, nullable: true)]
    protected $weight;

    /**
     * @var float
     */
    #[ORM\Column(name: 'tax_percent', type: 'percent', nullable: true)]
    protected $taxPercent;

    /**
     * @var double
     */
    #[ORM\Column(name: 'tax_amount', type: 'money', nullable: true)]
    protected $taxAmount;

    /**
     * @var float
     */
    #[ORM\Column(name: 'discount_percent', type: 'percent', nullable: true)]
    protected $discountPercent;

    /**
     * @var double
     */
    #[ORM\Column(name: 'discount_amount', type: 'money', nullable: true)]
    protected $discountAmount;

    /**
     * @var double
     */
    #[ORM\Column(name: 'row_total', type: 'money', nullable: true)]
    protected $rowTotal;

    #[ORM\ManyToOne(targetEntity: BaseOrder::class, cascade: ['persist'], inversedBy: 'items')]
    protected ?BaseOrder $order = null;

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param float $cost
     *
     * @return $this
     */
    public function setCost($cost)
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * @return float
     */
    public function getCost()
    {
        return $this->cost;
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
     * @param float $discountPercent
     *
     * @return $this
     */
    public function setDiscountPercent($discountPercent)
    {
        $this->discountPercent = $discountPercent;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountPercent()
    {
        return $this->discountPercent;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param float $price
     *
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param int $qty
     *
     * @return $this
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * @return int
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @param float $rowTotal
     *
     * @return $this
     */
    public function setRowTotal($rowTotal)
    {
        $this->rowTotal = $rowTotal;

        return $this;
    }

    /**
     * @return float
     */
    public function getRowTotal()
    {
        return $this->rowTotal;
    }

    /**
     * @param string $sku
     *
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
     * @param float $taxAmount
     *
     * @return $this
     */
    public function setTaxAmount($taxAmount)
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    /**
     * @return float
     */
    public function getTaxAmount()
    {
        return $this->taxAmount;
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

    /**
     * @param float $weight
     *
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
     * @param BaseOrder $order
     *
     * @return $this
     */
    public function setOrder(BaseOrder $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return BaseOrder
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getName();
    }
}

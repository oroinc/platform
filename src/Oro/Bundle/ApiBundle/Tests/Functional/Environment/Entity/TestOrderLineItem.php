<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_api_order_line_item")
 */
class TestOrderLineItem implements TestFrameworkEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var TestOrder
     *
     * @ORM\ManyToOne(targetEntity="TestOrder", inversedBy="lineItems")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $order;

    /**
     * @var TestProduct
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\TestFrameworkBundle\Entity\TestProduct")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $product;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float", nullable=true)
     */
    protected $quantity;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return TestOrder|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param TestOrder $order
     */
    public function setOrder(TestOrder $order)
    {
        $this->order = $order;
    }

    /**
     * {@inheritdoc}
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param TestProduct $product
     */
    public function setProduct(TestProduct $product)
    {
        $this->product = $product;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }
}

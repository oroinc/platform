<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;

#[ORM\Entity]
#[ORM\Table(name: 'test_api_order_line_item')]
class TestOrderLineItem implements TestFrameworkEntityInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TestOrder::class, inversedBy: 'lineItems')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?TestOrder $order = null;

    #[ORM\ManyToOne(targetEntity: TestProduct::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?TestProduct $product = null;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'quantity', type: Types::FLOAT, nullable: true)]
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

    public function setOrder(TestOrder $order)
    {
        $this->order = $order;
    }

    public function getProduct()
    {
        return $this->product;
    }

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

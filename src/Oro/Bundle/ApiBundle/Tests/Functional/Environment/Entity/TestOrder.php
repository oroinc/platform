<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_api_order")
 */
class TestOrder implements TestFrameworkEntityInterface
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
     * @var string
     *
     * @ORM\Column(name="po_number", type="string", length=255, nullable=true)
     */
    protected $poNumber;

    /**
     * @var Collection|TestOrderLineItem[]
     *
     * @ORM\OneToMany(targetEntity="TestOrderLineItem",
     *      mappedBy="order", cascade={"ALL"}, orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $lineItems;

    public function __construct()
    {
        $this->lineItems = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPoNumber()
    {
        return $this->poNumber;
    }

    /**
     * @param string $poNumber
     */
    public function setPoNumber($poNumber)
    {
        $this->poNumber = $poNumber;
    }

    /**
     * @return Collection|TestOrderLineItem[]
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @param Collection|TestOrderLineItem[] $lineItems
     */
    public function setLineItems(Collection $lineItems)
    {
        foreach ($lineItems as $lineItem) {
            $lineItem->setOrder($this);
        }
        $this->lineItems = $lineItems;
    }

    /**
     * @param TestOrderLineItem $lineItem
     */
    public function addLineItem(TestOrderLineItem $lineItem)
    {
        if (!$this->lineItems->contains($lineItem)) {
            $this->lineItems[] = $lineItem;
            $lineItem->setOrder($this);
        }
    }

    /**
     * @param TestOrderLineItem $lineItem
     */
    public function removeLineItem(TestOrderLineItem $lineItem)
    {
        if ($this->lineItems->contains($lineItem)) {
            $this->lineItems->removeElement($lineItem);
        }

        return $this;
    }
}

<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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

    /**
     * @var TestTarget|null
     *
     * @ORM\ManyToOne(targetEntity="TestTarget")
     * @ORM\JoinColumn(name="target_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $target;

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

    public function addLineItem(TestOrderLineItem $lineItem)
    {
        if (!$this->lineItems->contains($lineItem)) {
            $this->lineItems[] = $lineItem;
            $lineItem->setOrder($this);
        }
    }

    public function removeLineItem(TestOrderLineItem $lineItem)
    {
        if ($this->lineItems->contains($lineItem)) {
            $this->lineItems->removeElement($lineItem);
        }
    }

    /**
     * @return TestTarget|null
     */
    public function getTarget()
    {
        return $this->target;
    }

    public function setTarget(TestTarget $target = null)
    {
        $this->target = $target;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        if (null !== $this->target && null !== $this->target->name) {
            $targetNameLength = \strlen($this->target->name);
            if ($targetNameLength > 0 && $targetNameLength < 2) {
                $context->buildViolation('The name must have at least 2 symbols.')
                    ->atPath('target.name')
                    ->addViolation();
            }
        }
        foreach ($this->lineItems as $key => $lineItem) {
            $quantity = $lineItem->getQuantity();
            if (null !== $quantity && $quantity >= 1000) {
                $context->buildViolation('The quantity must be less than 1000.')
                    ->atPath('lineItems.' . $key . '.quantity')
                    ->addViolation();
            }
        }
    }
}

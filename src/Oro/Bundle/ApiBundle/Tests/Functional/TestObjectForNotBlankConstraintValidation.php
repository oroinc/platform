<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

class TestObjectForNotBlankConstraintValidation
{
    /**
     * @var ArrayCollection
     * @Assert\NotBlank()
     */
    private $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param mixed $value
     */
    public function addItem($value)
    {
        $this->items->add($value);
    }

    /**
     * @param mixed $value
     */
    public function removeItem($value)
    {
        $this->items->removeElement($value);
    }
}

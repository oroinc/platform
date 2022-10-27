<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Api\Stub;

use Oro\Bundle\CurrencyBundle\Entity\Price;

class PriceAwareEntityStub
{
    /** @var Price|null */
    private $price;

    /**
     * @return Price|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param Price $price
     */
    public function setPrice(Price $price = null)
    {
        $this->price = $price;
    }
}

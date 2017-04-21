<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

interface SettablePriceAwareInterface extends PriceAwareInterface
{
    /**
     * @param Price $price
     *
     * @return static
     */
    public function setPrice(Price $price);
}

<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

interface PriceAwareInterface
{
    /**
     * @return Price
     */
    public function getPrice();

    /**
     * @param Price $price
     * @return $this
     */
    public function setPrice(Price $price = null);
}

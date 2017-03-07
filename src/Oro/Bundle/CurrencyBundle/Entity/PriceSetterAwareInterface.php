<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

interface PriceSetterAwareInterface extends PriceAwareInterface
{
    /**
     * @param Price $price
     *
     * @return static
     */
    public function setPrice(Price $price);
}

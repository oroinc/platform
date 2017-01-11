<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

interface PriceAwareInterface
{
    /**
     * @return Price
     */
    public function getPrice();
}

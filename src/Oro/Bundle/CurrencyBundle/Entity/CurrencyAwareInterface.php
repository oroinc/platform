<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

interface CurrencyAwareInterface
{
    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency);
}

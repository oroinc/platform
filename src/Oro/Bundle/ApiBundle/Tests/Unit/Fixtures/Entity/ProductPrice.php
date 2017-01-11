<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

class ProductPrice
{
    /** @var string */
    protected $value;

    /** @var string */
    protected $currency;

    /**
     * @param string $value
     * @param string $currency
     */
    public function __construct($value = null, $currency = null)
    {
        $this->value = $value;
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return self
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }
}

<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Api\Stub;

class CurrencyAwareStub
{
    /** @var string|null */
    private $currency;

    public function __construct(string $currency = null)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrency(string $currency = null)
    {
        $this->currency = $currency;
    }

    /**
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->currency;
    }
}

<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Represents entity which stores value and currency as price item
 * @ORM\Embeddable()
 */
class Price implements CurrencyAwareInterface, \JsonSerializable
{
    const MAX_VALUE_SCALE = 4;

    use CurrencyAwareTrait;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="money", nullable=true)
     */
    protected $value;

    /**
     * @param string $value
     * @param string $currency
     * @return Price
     */
    public static function create($value, $currency)
    {
        /* @var $price self */
        $price = new static();
        $price->setValue($value)
            ->setCurrency($currency);

        return $price;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'value' => $this->value,
            'currency' => $this->currency
        ];
    }
}

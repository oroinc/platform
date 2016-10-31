<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

trait CurrencyAwareTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "dataaudit"={"auditable"=true, "immutable"=true},
     *      "importexport"={
     *          "order"=40
     *      }
     *  }
     * )
     */
    protected $currency;

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }
}

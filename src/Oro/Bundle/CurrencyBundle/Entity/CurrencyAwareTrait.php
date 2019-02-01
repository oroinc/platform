<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

trait CurrencyAwareTrait
{
    /**
     * @var string
     *
     * @Doctrine\ORM\Mapping\Column(name="currency", type="string", nullable=true)
     * @Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField(
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

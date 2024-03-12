<?php

namespace Oro\Bundle\CurrencyBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
* CurrencyAware trait
*
*/
trait CurrencyAwareTrait
{
    #[ORM\Column(name: 'currency', type: Types::STRING, nullable: true)]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => true, 'immutable' => true], 'importexport' => ['order' => 40]]
    )]
    protected ?string $currency = null;

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

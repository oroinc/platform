<?php
namespace Oro\Bundle\CurrencyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

class MultiCurrency
{
    use CurrencyAwareTrait;

    /**
     * @var double
     *
     * @ORM\Column(name="value", type="money", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "form"={
     *          "form_type"="oro_money",
     *          "form_options"={
     *              "constraints"={{"Range":{"min":0}}},
     *          }
     *      },
     *      "dataaudit"={
     *          "auditable"=true
     *      },
     *      "importexport"={
     *          "order"=50
     *      }
     *  }
     * )
     */
    protected $value;

    /**
     * @param string $value
     * @param string $currency
     * @return MultiCurrency
     */
    public static function create($value, $currency)
    {
        /* @var $multiCurrency self */
        $multiCurrency = new static();
        $multiCurrency->setValue($value)
            ->setCurrency($currency);

        return $multiCurrency;
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
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}

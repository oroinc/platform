<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="test_api_all_data_types")
 * @ORM\Entity
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class TestAllDataTypes implements TestFrameworkEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="field_string", type="string", nullable=true)
     */
    public $fieldString;

    /**
     * @var string
     *
     * @ORM\Column(name="field_text", type="text", nullable=true)
     */
    public $fieldText;

    /**
     * @var int
     *
     * @ORM\Column(name="field_int", type="integer", nullable=true)
     */
    public $fieldInt;

    /**
     * @var int
     *
     * @ORM\Column(name="field_smallint", type="smallint", nullable=true)
     */
    public $fieldSmallInt;

    /**
     * @var string
     *
     * @ORM\Column(name="field_bigint", type="bigint", nullable=true)
     */
    public $fieldBigInt;

    /**
     * @var bool
     *
     * @ORM\Column(name="field_boolean", type="boolean", nullable=true)
     */
    public $fieldBoolean;

    /**
     * @var string
     *
     * @ORM\Column(name="field_decimal", type="decimal", precision=10, scale=6, nullable=true)
     */
    public $fieldDecimal;

    /**
     * @var float
     *
     * @ORM\Column(name="field_float", type="float", nullable=true)
     */
    public $fieldFloat;

    /**
     * @var array
     *
     * @ORM\Column(name="field_array", type="array", nullable=true)
     */
    public $fieldArray;

    /**
     * @var array
     *
     * @ORM\Column(name="field_simple_array", type="simple_array", nullable=true)
     */
    public $fieldSimpleArray;

    /**
     * @var array
     *
     * @ORM\Column(name="field_json_array", type="json_array", nullable=true)
     */
    public $fieldJsonArray;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="field_datetime", type="datetime", nullable=true)
     */
    public $fieldDateTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="field_date", type="date", nullable=true)
     */
    public $fieldDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="field_time", type="time", nullable=true)
     */
    public $fieldTime;

    /**
     * @var string
     *
     * @ORM\Column(name="field_guid", type="guid", nullable=true)
     */
    public $fieldGuid;

    /**
     * @var float
     *
     * @ORM\Column(name="field_percent", type="percent", nullable=true)
     */
    public $fieldPercent;

    /**
     * @var string
     *
     * @ORM\Column(name="field_money", type="money", nullable=true)
     */
    public $fieldMoney;

    /**
     * @var integer
     *
     * @ORM\Column(name="field_duration", type="duration", nullable=true)
     */
    public $fieldDuration;

    /**
     * @var string
     *
     * @ORM\Column(name="field_money_value", type="money_value", nullable=true)
     */
    public $fieldMoneyValue;

    /**
     * @var string
     *
     * @ORM\Column(name="field_currency", type="currency", nullable=true)
     */
    public $fieldCurrency;
}

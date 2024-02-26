<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Functional\Environment\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'oro_test_money_entity')]
class TestMoneyEntity implements TestFrameworkEntityInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @var float
     */
    #[ORM\Column(name: 'money_property', type: 'money', nullable: true)]
    private $moneyProperty;

    /**
     * @var float
     */
    #[ORM\Column(name: 'money_value_property', type: 'money_value', nullable: true)]
    private $moneyValueProperty;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return float
     */
    public function getMoneyProperty()
    {
        return $this->moneyProperty;
    }

    /**
     * @param float $moneyProperty
     */
    public function setMoneyProperty($moneyProperty)
    {
        $this->moneyProperty = $moneyProperty;
    }

    /**
     * @return float
     */
    public function getMoneyValueProperty()
    {
        return $this->moneyValueProperty;
    }

    /**
     * @param float $moneyValueProperty
     */
    public function setMoneyValueProperty($moneyValueProperty)
    {
        $this->moneyValueProperty = $moneyValueProperty;
    }
}

<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Environment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="oro_test_decimal_entity")
 * @ORM\Entity
 */
class TestDecimalEntity implements TestFrameworkEntityInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var float
     *
     * @ORM\Column(name="decimal_property", type="decimal", nullable=true, precision=19, scale=4)
     */
    private $decimalProperty;

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
    public function getDecimalProperty()
    {
        return $this->decimalProperty;
    }

    /**
     * @param float $decimalProperty
     */
    public function setDecimalProperty($decimalProperty)
    {
        $this->decimalProperty = $decimalProperty;
    }
}

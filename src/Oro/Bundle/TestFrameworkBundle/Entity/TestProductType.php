<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Test Product Type
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'test_product_type')]
class TestProductType implements TestFrameworkEntityInterface
{
    #[ORM\Column(name: 'name', type: Types::STRING, length: 50)]
    #[ORM\Id]
    protected ?string $name = null;

    #[ORM\Column(name: 'label', type: Types::STRING, nullable: true)]
    protected ?string $label = null;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
}

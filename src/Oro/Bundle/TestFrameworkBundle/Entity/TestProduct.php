<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Test Product
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'test_product')]
class TestProduct implements TestFrameworkEntityInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: true)]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: TestProductType::class)]
    #[ORM\JoinColumn(name: 'product_type', referencedColumnName: 'name', onDelete: 'SET NULL')]
    protected ?TestProductType $productType = null;

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
     * @return TestProductType|null
     */
    public function getProductType()
    {
        return $this->productType;
    }

    public function setProductType(TestProductType $productType = null)
    {
        $this->productType = $productType;
    }
}

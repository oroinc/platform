<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="test_product")
 * @ORM\Entity
 */
class TestProduct implements TestFrameworkEntityInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    protected $name;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="TestProductType")
     * @ORM\JoinColumn(name="product_type", referencedColumnName="name", onDelete="SET NULL")
     */
    protected $productType;

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

    /**
     * @param TestProductType|null $productType
     */
    public function setProductType(TestProductType $productType = null)
    {
        $this->productType = $productType;
    }
}

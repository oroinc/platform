<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_security_order")
 */
class TestSecurityOrder implements TestFrameworkEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="po_number", type="string", length=255, nullable=true)
     */
    protected $poNumber;

    /**
     * @var TestSecurityPerson
     *
     * @ORM\ManyToOne(targetEntity="TestSecurityPerson")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $person;

    /**
     * @var TestSecurityProduct[]
     *
     * @ORM\ManyToMany(
     *     targetEntity="Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity\TestSecurityProduct",
     *     inversedBy="orders"
     * )
     * @ORM\JoinTable(name="test_security_order_product",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="business_unit_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

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
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getPoNumber(): string
    {
        return $this->poNumber;
    }

    /**
     * @param string $poNumber
     */
    public function setPoNumber(string $poNumber): void
    {
        $this->poNumber = $poNumber;
    }

    /**
     * @return TestSecurityPerson
     */
    public function getPerson(): TestSecurityPerson
    {
        return $this->person;
    }

    /**
     * @param TestSecurityPerson $person
     */
    public function setPerson(TestSecurityPerson $person): void
    {
        $this->person = $person;
    }

    /**
     * @return TestSecurityProduct[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @param TestSecurityProduct[] $products
     */
    public function setProducts(array $products): void
    {
        $this->products = $products;
    }
}

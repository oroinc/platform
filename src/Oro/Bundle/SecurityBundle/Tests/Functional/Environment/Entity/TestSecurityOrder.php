<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'test_security_order')]
class TestSecurityOrder implements TestFrameworkEntityInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'po_number', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $poNumber = null;

    #[ORM\ManyToOne(targetEntity: TestSecurityPerson::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?TestSecurityPerson $person = null;

    /**
     * @var array|Collection<int, TestSecurityProduct>|null
     */
    #[ORM\ManyToMany(targetEntity: TestSecurityProduct::class, inversedBy: 'orders')]
    #[ORM\JoinTable(name: 'test_security_order_product')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'business_unit_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
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

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getPoNumber(): string
    {
        return $this->poNumber;
    }

    public function setPoNumber(string $poNumber): void
    {
        $this->poNumber = $poNumber;
    }

    public function getPerson(): TestSecurityPerson
    {
        return $this->person;
    }

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

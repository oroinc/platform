<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class TestProduct
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var Collection<int, TestOrder>
     */
    #[ORM\ManyToMany(targetEntity: TestOrder::class, mappedBy: 'products')]
    protected ?Collection $orders = null;

    /**
     * @var Collection<int, TestProductNote>
     */
    #[ORM\ManyToMany(targetEntity: TestProductNote::class, inversedBy: 'products')]
    protected ?Collection $notes = null;

    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        $this->id     = $id;
        $this->orders = new ArrayCollection();
        $this->notes = new ArrayCollection();
    }
}

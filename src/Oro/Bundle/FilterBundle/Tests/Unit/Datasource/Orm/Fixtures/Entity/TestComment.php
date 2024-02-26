<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class TestComment
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TestProduct::class)]
    protected ?TestProduct $products = null;

    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        $this->id       = $id;
        $this->products = new ArrayCollection();
    }
}

<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Datasource\Orm\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class TestProduct
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="TestOrder", mappedBy="products")
     */
    protected $orders;

    /**
     * @ORM\ManyToMany(targetEntity="TestProductNote", inversedBy="products")
     */
    protected $notes;

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

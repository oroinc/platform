<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait ArrayFieldTypeTrait
{
    /**
     * @var array
     *
     * @ORM\Column(name="old_array", type="array", nullable=true)
     */
    protected $oldArray;

    /**
     * @var array
     *
     * @ORM\Column(name="old_simplearray", type="simple_array", nullable=true)
     */
    protected $oldSimplearray;

    /**
     * @var array
     *
     * @ORM\Column(name="old_jsonarray", type="json_array", nullable=true)
     */
    protected $oldJsonarray;

    /**
     * @var array
     *
     * @ORM\Column(name="new_array", type="array", nullable=true)
     */
    protected $newArray;

    /**
     * @var array
     *
     * @ORM\Column(name="new_simplearray", type="simple_array", nullable=true)
     */
    protected $newSimplearray;

    /**
     * @var array
     *
     * @ORM\Column(name="new_jsonarray", type="json_array", nullable=true)
     */
    protected $newJsonarray;
}

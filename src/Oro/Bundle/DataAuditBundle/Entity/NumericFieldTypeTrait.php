<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait NumericFieldTypeTrait
{
    /**
     * @var int
     *
     * @ORM\Column(name="old_integer", type="bigint", nullable=true)
     */
    protected $oldInteger;

    /**
     * @var float
     *
     * @ORM\Column(name="old_float", type="float", nullable=true)
     */
    protected $oldFloat;

    /**
     * @var int
     *
     * @ORM\Column(name="new_integer", type="bigint", nullable=true)
     */
    protected $newInteger;

    /**
     * @var int
     *
     * @ORM\Column(name="new_float", type="float", nullable=true)
     */
    protected $newFloat;
}

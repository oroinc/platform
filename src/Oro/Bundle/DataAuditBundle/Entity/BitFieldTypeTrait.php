<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait BitFieldTypeTrait
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="old_boolean", type="boolean", nullable=true)
     */
    protected $oldBoolean;

    /**
     * @var bool
     *
     * @ORM\Column(name="new_boolean", type="boolean", nullable=true)
     */
    protected $newBoolean;
}

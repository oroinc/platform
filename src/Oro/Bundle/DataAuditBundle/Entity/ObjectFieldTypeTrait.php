<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

trait ObjectFieldTypeTrait
{
    /**
     * @var object
     *
     * @ORM\Column(name="old_object", type="object", nullable=true)
     */
    protected $oldObject;

    /**
     * @var object
     *
     * @ORM\Column(name="new_object", type="object", nullable=true)
     */
    protected $newObject;
}

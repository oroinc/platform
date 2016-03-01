<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait StringFieldTypeTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="old_text", type="text", nullable=true)
     */
    protected $oldText;

    /**
     * @var string
     *
     * @ORM\Column(name="new_text", type="text", nullable=true)
     */
    protected $newText;
}

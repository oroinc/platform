<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* BitFieldType trait
*
*/
trait BitFieldTypeTrait
{
    #[ORM\Column(name: 'old_boolean', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $oldBoolean = null;

    #[ORM\Column(name: 'new_boolean', type: Types::BOOLEAN, nullable: true)]
    protected ?bool $newBoolean = null;
}

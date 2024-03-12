<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* StringFieldType trait
*
*/
trait StringFieldTypeTrait
{
    #[ORM\Column(name: 'old_text', type: Types::TEXT, nullable: true)]
    protected ?string $oldText = null;

    #[ORM\Column(name: 'new_text', type: Types::TEXT, nullable: true)]
    protected ?string $newText = null;
}

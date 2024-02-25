<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* NumericFieldType trait
*
*/
trait NumericFieldTypeTrait
{
    /**
     * @var int|null
     */
    #[ORM\Column(name: 'old_integer', type: Types::BIGINT, nullable: true)]
    protected $oldInteger;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'old_float', type: Types::FLOAT, nullable: true)]
    protected $oldFloat;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'new_integer', type: Types::BIGINT, nullable: true)]
    protected $newInteger;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'new_float', type: Types::FLOAT, nullable: true)]
    protected $newFloat;
}

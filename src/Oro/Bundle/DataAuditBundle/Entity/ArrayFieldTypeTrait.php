<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* ArrayFieldType trait
*
*/
trait ArrayFieldTypeTrait
{
    /**
     * @var array
     */
    #[ORM\Column(name: 'old_array', type: Types::ARRAY, nullable: true)]
    protected $oldArray;

    /**
     * @var array
     */
    #[ORM\Column(name: 'old_simplearray', type: Types::SIMPLE_ARRAY, nullable: true)]
    protected $oldSimplearray;

    /**
     * @var array
     */
    #[ORM\Column(name: 'old_jsonarray', type: 'json_array', nullable: true)]
    protected $oldJsonarray;

    /**
     * @var array
     */
    #[ORM\Column(name: 'new_array', type: Types::ARRAY, nullable: true)]
    protected $newArray;

    /**
     * @var array
     */
    #[ORM\Column(name: 'new_simplearray', type: Types::SIMPLE_ARRAY, nullable: true)]
    protected $newSimplearray;

    /**
     * @var array
     */
    #[ORM\Column(name: 'new_jsonarray', type: 'json_array', nullable: true)]
    protected $newJsonarray;
}

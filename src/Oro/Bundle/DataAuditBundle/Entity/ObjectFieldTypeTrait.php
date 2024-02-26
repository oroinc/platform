<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* ObjectFieldType trait
*
*/
trait ObjectFieldTypeTrait
{
    /**
     * @var object
     */
    #[ORM\Column(name: 'old_object', type: Types::OBJECT, nullable: true)]
    protected $oldObject;

    /**
     * @var object
     */
    #[ORM\Column(name: 'new_object', type: Types::OBJECT, nullable: true)]
    protected $newObject;
}

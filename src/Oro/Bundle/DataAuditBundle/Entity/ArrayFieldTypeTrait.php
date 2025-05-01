<?php

declare(strict_types=1);

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* ArrayFieldType trait
*/
trait ArrayFieldTypeTrait
{
    #[ORM\Column(name: 'old_array', type: Types::ARRAY, nullable: true)]
    protected ?array $oldArray;

    #[ORM\Column(name: 'old_simplearray', type: Types::SIMPLE_ARRAY, nullable: true)]
    protected ?array $oldSimplearray;

    #[ORM\Column(name: 'old_jsonarray', type: 'json_array', nullable: true)]
    protected ?array $oldJsonarray;

    #[ORM\Column(name: 'old_json', type: Types::JSON, nullable: true)]
    protected ?array $oldJson;

    #[ORM\Column(name: 'new_array', type: Types::ARRAY, nullable: true)]
    protected ?array $newArray;

    #[ORM\Column(name: 'new_simplearray', type: Types::SIMPLE_ARRAY, nullable: true)]
    protected ?array $newSimplearray;

    #[ORM\Column(name: 'new_jsonarray', type: 'json_array', nullable: true)]
    protected ?array $newJsonarray;

    #[ORM\Column(name: 'new_json', type: Types::JSON, nullable: true)]
    protected ?array $newJson;
}

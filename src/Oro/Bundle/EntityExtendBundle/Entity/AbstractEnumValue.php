<?php

namespace Oro\Bundle\EntityExtendBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A base class that is only needed to migrate outdated enum`s during a platform update.
 *
 * @deprecated
 */
#[ORM\MappedSuperclass]
abstract class AbstractEnumValue
{
    #[ORM\Column(name: 'id', type: Types::STRING, length: 32)]
    #[ORM\Id]
    private ?string $id = null;
}

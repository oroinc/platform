<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroAddressBundle_Entity_Address;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Address Entity
 *
 * @mixin OroAddressBundle_Entity_Address
 */
#[ORM\Entity]
#[ORM\Table('oro_address')]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-map-marker'],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class Address extends AbstractAddress implements ExtendEntityInterface
{
    use ExtendEntityTrait;
}

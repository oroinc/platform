<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Address
 *
 * @ORM\Table("oro_address")
 * @ORM\Entity
 * @Config()
 */
class Address extends AbstractAddress
{
}

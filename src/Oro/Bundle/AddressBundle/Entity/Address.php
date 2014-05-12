<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Address
 *
 * @ORM\Table("oro_address")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={"icon"="icon-map-marker"},
 *      }
 * )
 */
class Address extends AbstractAddress
{
}

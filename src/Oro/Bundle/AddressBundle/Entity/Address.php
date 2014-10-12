<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\AddressBundle\Model\ExtendAddress;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Address
 *
 * @ORM\Table("oro_address")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-map-marker"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class Address extends ExtendAddress
{
}

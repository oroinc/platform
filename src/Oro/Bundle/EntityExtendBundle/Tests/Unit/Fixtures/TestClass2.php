<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Entity()
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *  routeName="oro_entity_index",
 *  routeView="oro_entity_view",
 *  defaultValues={
 *      "ownership"={
 *          "owner_type"="USER",
 *          "owner_field_name"="owner",
 *          "owner_column_name"="user_owner_id"
 *      },
 *      "security"={
 *          "type"="ACL",
 *          "group_name"=""
 *      }
 *  }
 * )
 */
class TestClass2 extends ExtendClass
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $description;
}
